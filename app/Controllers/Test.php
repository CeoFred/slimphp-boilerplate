<?php

namespace Test;

/**Set up your model */
use App\Models\Post;

class Test  {

    public function all($request,$response){
        return 'Get all posts';
        // $this->view->render($response,'hiii');
    }

    public function getSingle($request,$response,$argv){

        return Post::where('id',$argv['id'])->first();
    }
    
    public function post($request,$response){
        /**
         * Get all request body parameters
         * 
         */
        $body = $request->getParams();
        Post::create($body);
    }

    public function update($request,$response,$argv){
        $id = $argv['id'];
       $post =  Post::findOrFail($id);
       $post->title =  $request->getParam('title');
       $post->body =  $request->getParam('body');
       $post->save();
    }

    public function delete($request,$response,$args){
        $id = $args['id'];
        Post::where('id',$id)->delete();

    }

}