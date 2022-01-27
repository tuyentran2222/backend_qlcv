<?php

namespace App\Http\Controllers;

use App\Events\CommentEvent;
use Illuminate\Http\Request;
use App\Models\Comment;
use App\Repositories\Comment\CommentInterface;
use CommentsTableSeeder;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Helper;
use Illuminate\Auth\Access\AuthorizationException;

class CommentController extends Controller
{
    private CommentInterface $commentInterface;
    public function __construct(CommentInterface $commentRepository)
    {
        $this->commentInterface = $commentRepository;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(int $id, Request $request){
        $action = "create comment";
        $user = Helper::getUser();
        $commentArray = [
            'content' => $request->comment,
            'task_id' => $id,
            'user_id' => $user->id
        ];
    
        $rules = $this->getCommentRulesValidation();
        $validator = Validator::make($commentArray, $rules);
        
        if (empty($validator->errors())) {
            return  Helper::getResponseJson(400, "Thêm bình luận thất bại", [], $action ,$validator->errors());
        }

        $comment = $this->commentInterface->create($commentArray);
        
        $dataReturn = [
            'message' => $comment->content,
            'time' => $comment->created_at,
            'name' => $comment->user()->get()->first()->username,
            'avatar' => $comment->user()->get()->first()->avatar,
            'id' => $user->id,
            'comment_id' => $comment->id
        ];
        
        broadcast(new CommentEvent($user, $comment->content))->toOthers();

        return Helper::getResponseJson(200, 'Thêm bình luận thành công', $dataReturn, $action);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {   //can update
        $user = Helper::getUser();
        $action = "update comment";
        if (!$id || !$request->comment) 
            return  Helper::getResponseJson(404, "Chưa có id của bình luận hoặc bình luận trống", [], $action);

        $commentContent = $request->comment;
        $comment = $this->commentInterface->find($id);
        if (!$comment) 
            return  Helper::getResponseJson(404, "Không có bình luận có id = ". $id, [], $action);

        $checkAuthorization = false;
        try {
            $checkAuthorization = $this->authorize('update', $comment);
        } catch (AuthorizationException $e) {

        }

        if (!$checkAuthorization) 
            return  Helper::getResponseJson(404, "Không phải bình luận của bạn nên không thể sửa.", [], $action);

        $comment = $this->commentInterface->update($comment->id, ['content' => $commentContent]);
        return  Helper::getResponseJson(200, "Cập nhật bình luận thành công", ['comment' => $comment], $action);
    
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        $action = "delete message";
        $comment = $this->commentInterface->find($id);

        if (! $comment) 
            return Helper::getResponseJson(404, "Không có bình luận có id = ". $id, [], $action);

        $checkAuthorization = false;
        try {
            $checkAuthorization = $this->authorize('update',$comment);
        } catch (AuthorizationException $e) {

        }
        if (!$checkAuthorization) return  Helper::getResponseJson(404, "Không phải bình luận của bạn nên không thể sửa.", [], $action);

        $this->commentInterface->delete($id);
        return  Helper::getResponseJson(200, 'Xóa bình luận thành công.', [], $action);
    }

    /**
     * get comment rules validation
     */
    public function getCommentRulesValidation() {
        return 
        [
            'content' => 'required|max:255',
            'task_id' => 'required|integer',
            'user_id' => 'required|integer'
        ];
    }
}
