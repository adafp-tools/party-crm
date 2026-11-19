<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'member_id' => ['required', 'uuid', 'exists:members,id'],
            'photo' => ['required', 'image', 'mimes:jpeg', 'max:300'],
        ]);

        $member = Member::findOrFail($request->member_id);

        $this->authorize('update', $member);

        if ($member->photo_path) {
            Storage::disk('private')->delete($member->photo_path);
        }

        $path = $request->file('photo')->store(
            'member-photos/'.$member->org_unit_id,
            'private'
        );

        $member->update(['photo_path' => $path, 'updated_by' => $request->user()->id]);

        return response()->json([
            'photo_path' => $path,
            'message' => __('Photo uploaded.'),
        ]);
    }
}
