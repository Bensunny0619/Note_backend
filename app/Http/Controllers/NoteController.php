<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Label;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Events\NoteCreated;
use App\Events\NoteUpdated;
use App\Events\NoteDeleted;

class NoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Note::where('user_id', Auth::id());

        // Search
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('content', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by Label
        if ($request->has('label_id')) {
            $query->whereHas('labels', function($q) use ($request) {
                $q->where('labels.id', $request->label_id);
            });
        }

        // Archived/Unarchived
        if ($request->has('is_archived')) {
            $query->where('is_archived', filter_var($request->is_archived, FILTER_VALIDATE_BOOLEAN));
        } else {
            $query->where('is_archived', false);
        }

        // Ordering: Pinned first, then by position (if implemented) or created_at desc
        $query->orderBy('is_pinned', 'desc')
              ->orderBy('position', 'asc')
              ->orderBy('created_at', 'desc');

        $notes = $query->with(['checklistItems', 'labels', 'images', 'reminder', 'sharedWith', 'audioRecordings', 'drawings'])
                       ->paginate(20);

        return response()->json($notes);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'is_pinned' => 'boolean',
            'is_archived' => 'boolean',
            'audio_uri' => 'nullable|file|mimes:mp3,wav,ogg,m4a,aac|max:20480', // 20MB max
            'drawing_base64' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $note = Auth::user()->notes()->create($request->except(['audio_uri', 'drawing_base64']));

        // Handle Audio
        if ($request->hasFile('audio_uri')) {
            $file = $request->file('audio_uri');
            $path = $file->store('note-audio', 'public');
            
            $note->audioRecordings()->create([
                'audio_path' => $path,
                'audio_url' => asset('storage/' . $path),
                'format' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'duration' => $request->audio_duration ?? 0,
            ]);
        }

        // Handle Drawing (Base64)
        if ($request->has('drawing_base64') && !empty($request->drawing_base64)) {
            $image = $request->drawing_base64;
            // Allow string to contain data URI prefix or just base64
            if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
                $image = substr($image, strpos($image, ',') + 1);
                $type = strtolower($type[1]); // jpg, png, gif
                if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                    $type = 'png';
                }
            } else {
                $type = 'png';
            }

            $image = str_replace(' ', '+', $image);
            $imageName = 'drawing_' . time() . '.' . $type;
            $path = 'note-drawings/' . $imageName;
            
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, base64_decode($image));

            $note->drawings()->create([
                'drawing_path' => $path,
                'drawing_url' => asset('storage/' . $path),
                'canvas_width' => $request->canvas_width ?? 800,
                'canvas_height' => $request->canvas_height ?? 600,
                'format' => $type,
            ]);
        }

        // Broadcast event
        broadcast(new NoteCreated($note->refresh()))->toOthers();

        return response()->json($note->load(['checklistItems', 'labels', 'images', 'audioRecordings', 'drawings']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $notes = Note::with(['checklistItems', 'labels', 'images', 'reminder', 'sharedWith', 'audioRecordings', 'drawings'])
                    ->where('user_id', Auth::id())
                    ->findOrFail($id);

        return response()->json($notes);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $note = Note::where('user_id', Auth::id())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'is_pinned' => 'boolean',
            'is_archived' => 'boolean',
            'position' => 'integer'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $note->update($request->all());

        // Broadcast event
        broadcast(new NoteUpdated($note->fresh()))->toOthers();

        return response()->json($note->load(['checklistItems', 'labels', 'images']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $note = Note::where('user_id', Auth::id())->findOrFail($id);
        $userId = $note->user_id;
        $note->delete();

        // Broadcast event
        broadcast(new NoteDeleted((int)$id, $userId))->toOthers();

        return response()->json(['message' => 'Note deleted successfully']);
    }

    // Additional Actions

    public function pin(string $id)
    {
        $note = Note::where('user_id', Auth::id())->findOrFail($id);
        $note->update(['is_pinned' => true]);
        broadcast(new NoteUpdated($note->fresh()))->toOthers();
        return response()->json(['message' => 'Note pinned', 'note' => $note]);
    }

    public function unpin(string $id)
    {
        $note = Note::where('user_id', Auth::id())->findOrFail($id);
        $note->update(['is_pinned' => false]);
        broadcast(new NoteUpdated($note->fresh()))->toOthers();
        return response()->json(['message' => 'Note unpinned', 'note' => $note]);
    }

    public function archive(string $id)
    {
        $note = Note::where('user_id', Auth::id())->findOrFail($id);
        $note->update(['is_archived' => true]);
        broadcast(new NoteUpdated($note->fresh()))->toOthers();
        return response()->json(['message' => 'Note archived', 'note' => $note]);
    }

    public function unarchive(string $id)
    {
        $note = Note::where('user_id', Auth::id())->findOrFail($id);
        $note->update(['is_archived' => false]);
        broadcast(new NoteUpdated($note->fresh()))->toOthers();
        return response()->json(['message' => 'Note unarchived', 'note' => $note]);
    }

    public function color(Request $request, string $id)
    {
        $request->validate(['color' => 'required|string|max:7']);
        $note = Note::where('user_id', Auth::id())->findOrFail($id);
        $note->update(['color' => $request->color]);
        broadcast(new NoteUpdated($note->fresh()))->toOthers();
        return response()->json(['message' => 'Color updated', 'note' => $note]);
    }

    public function uploadAudio(Request $request, $id)
    {
        \Illuminate\Support\Facades\Log::info("Audio Upload Request for Note ID: $id", [
            'has_file' => $request->hasFile('audio'),
            'all_files' => $request->allFiles(),
            'headers' => $request->headers->all(),
            'post_data' => $request->all()
        ]);

        $note = Note::where('user_id', Auth::id())->findOrFail($id);
        if ($request->hasFile('audio')) {
            $file = $request->file('audio');
            $path = $file->store('note-audio', 'public');
            
            $recording = $note->audioRecordings()->create([
                'audio_path' => $path,
                'audio_url' => asset('storage/' . $path),
                'format' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'duration' => $request->duration ?? 0,
            ]);
            
            return response()->json($recording, 201);
        }
        return response()->json(['error' => 'No audio file provided', 'debug_files' => $request->allFiles()], 422);
    }

    public function uploadDrawing(Request $request, $id)
    {
        \Illuminate\Support\Facades\Log::info("Drawing Upload Request for Note ID: $id", [
            'has_file' => $request->hasFile('drawing'),
            'all_files' => $request->allFiles(),
            'headers' => $request->headers->all(),
            'post_data' => $request->all()
        ]);

        $note = Note::where('user_id', Auth::id())->findOrFail($id);
        if ($request->hasFile('drawing')) {
            $file = $request->file('drawing');
            $path = $file->store('note-drawings', 'public');
            
            $drawing = $note->drawings()->create([
                'drawing_path' => $path,
                'drawing_url' => asset('storage/' . $path),
                'canvas_width' => $request->width ?? 800,
                'canvas_height' => $request->height ?? 600,
                'format' => 'png', 
            ]);
            
            return response()->json($drawing, 201);
        }
        return response()->json(['error' => 'No drawing file provided', 'debug_files' => $request->allFiles()], 422);
    }
}
