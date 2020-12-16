<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Article;

use Carbon\Carbon;

class ArticleController extends Controller {


	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
	}

	function index()
	{
	    $articles = Article::get();
        return 	view('articles', ['articles' => $articles]);

	}

    function article(Request $request, $id)
    {
        $article = Article::find($id);
        return 	view('article', ['article' => $article]);
    }

}
