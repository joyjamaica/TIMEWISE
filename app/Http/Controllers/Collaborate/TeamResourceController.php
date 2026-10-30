<?php

namespace App\Http\Controllers\Collaborate;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use App\Models\Team;
use App\Models\TeamResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeamResourceController extends Controller
{
    public function store(Request $request, Team $team)
    {
        Gate::authorize('view', $team);

        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,txt,md,csv,xls,xlsx,ppt,pptx,png,jpg,jpeg'],
            'title' => ['nullable', 'string', 'max:120'],
        ], [
            'file.max' => 'Files can be up to 10 MB.',
            'file.mimes' => 'Upload a PDF, Office document, text, CSV or image file.',
        ]);

        $file = $request->file('file');

        $team->resources()->create([
            'uploaded_by' => $request->user()->id,
            'title' => $request->input('title') ?: $file->getClientOriginalName(),
            'path' => $file->store("team-resources/{$team->id}", 'local'), // private disk
            'extension' => strtolower($file->getClientOriginalExtension()),
            'size' => $file->getSize(),
        ]);

        return back()->with('status', 'Resource uploaded.');
    }

    public function download(Team $team, TeamResource $resource)
    {
        Gate::authorize('view', $team);
        abort_unless(Storage::disk('local')->exists($resource->path), 404);

        $name = $resource->title;
        if ($resource->extension && ! str_ends_with(strtolower($name), '.'.$resource->extension)) {
            $name .= '.'.$resource->extension;
        }

        return Storage::disk('local')->download($resource->path, $name);
    }

    public function destroy(Request $request, Team $team, TeamResource $resource)
    {
        Gate::authorize('view', $team);
        abort_unless(
            $request->user()->id === $team->owner_id || $request->user()->id === $resource->uploaded_by,
            403
        );

        Storage::disk('local')->delete($resource->path);
        $resource->delete();

        return back()->with('status', 'Resource removed.');
    }
}
