<?php

namespace App\Http\Controllers;

use App\Http\Requests\WebsiteSubmissionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WebsiteSubmissionController extends Controller
{
    public function __invoke(WebsiteSubmissionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $isProduction = app()->environment('production');
        $isEnglish = $request->cookie('findo_language') === 'en';
        $id = DB::table('form_submissions')->insertGetId([
            'form_key' => $data['form_key'], 'source_path' => $data['source_path'],
            'payload' => json_encode(collect($data)->except(['form_key', 'source_path', 'website'])->all(), JSON_UNESCAPED_UNICODE),
            'status' => $isProduction ? 'new' : 'received_locally', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $message = $isProduction
            ? ($isEnglish ? 'Your request has been received successfully.' : 'تم استلام طلبك بنجاح.')
            : ($isEnglish ? 'Your request was saved in the local version.' : 'تم حفظ طلبك في النسخة المحلية بنجاح.');

        return response()->json(['id' => $id, 'message' => $message, 'delivery' => $isProduction ? 'inbox' : 'local_only'], 201);
    }
}
