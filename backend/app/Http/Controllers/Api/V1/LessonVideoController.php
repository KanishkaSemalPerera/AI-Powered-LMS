<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Services\LessonVideoService;
use Symfony\Component\HttpFoundation\Response;

/**
 * GET /lessons/{lesson}/video?expires=...&signature=...
 *
 * Plays an uploaded lesson video. Access is granted by the URL signature
 * (handed out only to authorised users by LessonResource), because a
 * <video> element cannot send an Authorization header.
 */
class LessonVideoController extends Controller
{
    public function __invoke(Lesson $lesson, LessonVideoService $videos): Response
    {
        return $videos->streamResponse($lesson);
    }
}
