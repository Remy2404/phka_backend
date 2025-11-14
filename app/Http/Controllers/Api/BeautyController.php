<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BeautyQuiz;
use App\Models\BeautyTip;
use App\Models\TutorialVideo;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Beauty",
 *     description="Beauty content and quiz endpoints"
 * )
 */
class BeautyController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/beauty/tips",
     *     tags={"Beauty"},
     *     summary="Get beauty tips",
     *     description="Returns a list of beauty tips and advice",
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         required=false,
     *         description="Filter by category",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="skin_type",
     *         in="query",
     *         required=false,
     *         description="Filter by skin type",
     *         @OA\Schema(type="string", enum={"normal","dry","oily","combination","sensitive"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of beauty tips",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function tips(Request $request)
    {
        $query = BeautyTip::where('is_published', true);

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('skin_type')) {
            $query->where('target_skin_types', 'like', '%' . $request->skin_type . '%');
        }

        $tips = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $tips
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/beauty/tips/{id}",
     *     tags={"Beauty"},
     *     summary="Get beauty tip details",
     *     description="Returns detailed information about a specific beauty tip",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Beauty tip ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Beauty tip details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Beauty tip not found")
     * )
     */
    public function tipShow($id)
    {
        $tip = BeautyTip::where('is_published', true)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $tip
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/beauty/tutorials",
     *     tags={"Beauty"},
     *     summary="Get tutorial videos",
     *     description="Returns a list of beauty tutorial videos",
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         required=false,
     *         description="Filter by category",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="difficulty",
     *         in="query",
     *         required=false,
     *         description="Filter by difficulty level",
     *         @OA\Schema(type="string", enum={"beginner","intermediate","advanced"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of tutorial videos",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function tutorials(Request $request)
    {
        $query = TutorialVideo::where('is_published', true);

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('difficulty')) {
            $query->where('difficulty_level', $request->difficulty);
        }

        $tutorials = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $tutorials
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/beauty/tutorials/{id}",
     *     tags={"Beauty"},
     *     summary="Get tutorial video details",
     *     description="Returns detailed information about a specific tutorial video",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Tutorial video ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tutorial video details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Tutorial not found")
     * )
     */
    public function tutorialShow($id)
    {
        $tutorial = TutorialVideo::where('is_published', true)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $tutorial
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/beauty/quizzes",
     *     tags={"Beauty"},
     *     summary="Get beauty quizzes",
     *     description="Returns a list of available beauty quizzes",
     *     @OA\Response(
     *         response=200,
     *         description="List of beauty quizzes",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function quizzes()
    {
        $quizzes = BeautyQuiz::where('is_active', true)
            ->with('questions')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $quizzes
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/beauty/quizzes/{id}",
     *     tags={"Beauty"},
     *     summary="Get beauty quiz details",
     *     description="Returns detailed information about a specific beauty quiz including questions",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Beauty quiz ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Beauty quiz details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Quiz not found")
     * )
     */
    public function quizShow($id)
    {
        $quiz = BeautyQuiz::where('is_active', true)
            ->with('questions')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $quiz
        ]);
    }
}