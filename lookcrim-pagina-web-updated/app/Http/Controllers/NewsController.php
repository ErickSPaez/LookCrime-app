<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Models\News;

class NewsController extends Controller {

    public function __construct()
    {
        // Protect administrative actions; allow public listing and viewing
        $this->middleware('auth')->except(['index', 'show']);
    }

    public function create() {
        $news = new News;
        return view('news.create', ['news' => $news]);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'title_pt' => 'required|string',
            'title_en' => 'required|string',
            'content_pt' => 'required|string',
            'content_en' => 'required|string',
            'image' => 'nullable|file|image|max:5120',
        ]);

        $news = new News;
        $news->title_pt = html_entity_decode($data['title_pt']);
        $news->title_en = html_entity_decode($data['title_en']);
        $news->content_pt = $data['content_pt'];
        $news->content_en = $data['content_en'];
        $news->save(); // get id

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $image = $request->file('image');
            $ext = $image->getClientOriginalExtension();
            $filename = $news->id . '.' . $ext;
            // store in storage/app/public/news
            $image->storeAs('public/news', $filename);
            $news->image = 'storage/news/' . $filename;
        }

        $news->embed_url = $request->input('embed_url');
        $news->embed_url_en = $request->input('embed_url_en');

        $news->private = $request->has('private') ? 1 : 0;
        $news->highlight = $request->has('highlight') ? 1 : 0;
        $news->save();

        return redirect()->route('news');
    }

    public function edit($id) {
        $news = News::findOrFail($id);
        return view('news.edit', ['news' => $news]);
    }

    public function update($id, Request $request) {
        $data = $request->validate([
            'title_pt' => 'required|string',
            'title_en' => 'required|string',
            'content_pt' => 'required|string',
            'content_en' => 'required|string',
            'image' => 'nullable|file|image|max:5120',
        ]);

        $news = News::findOrFail($id);
        $news->title_pt = html_entity_decode($data['title_pt']);
        $news->title_en = html_entity_decode($data['title_en']);
        $news->content_pt = $data['content_pt'];
        $news->content_en = $data['content_en'];

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $image = $request->file('image');
            $ext = $image->getClientOriginalExtension();
            $filename = $news->id . '.' . $ext;
            $image->storeAs('public/news', $filename);
            $news->image = 'storage/news/' . $filename;
        }

        $news->embed_url = $request->input('embed_url');
        $news->embed_url_en = $request->input('embed_url_en');
        $news->private = $request->has('private') ? 1 : 0;
        $news->highlight = $request->has('highlight') ? 1 : 0;
        $news->save();

        return redirect()->route('news');
    }

    public function confirmDelete($id) {
        $news = News::findOrFail($id);
        return view('news.delete', ['news' => $news]);
    }

    public function delete($id, Request $request) {
        if ($request->input('confirm') === 'yes') {
            News::findOrFail($id)->delete();
        }
        return redirect()->route('news');
    }

    public function index() {
        if (Auth::check()) {
            $news = News::orderBy('created_at', 'DESC')->paginate(15);
        } else {
            $news = News::where('private', '=', 0)
                ->orderBy('created_at', 'DESC')
                ->paginate(15);
        }
        return view('news.list', ['news' => $news]);
    }

    public function show($id) {
        $news = News::findOrFail($id);
        if ($news->private != 0 && !Auth::check()) {
            return redirect()->route('news');
        }
        return view('news.show', ['news' => $news]);
    }

}
