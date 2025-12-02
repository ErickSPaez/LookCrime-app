<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

use App\Models\Newsletter;
use App\Models\NewsletterSection;
use App\Models\NewsletterSubscriber;
use App\Mail\NewsletterMailable;
use App\Mail\SubscriberConfirmation;

class NewsletterController extends Controller {

    public function __construct()
    {
        // Protect admin newsletter actions; allow public subscribe/confirm/preview
        $this->middleware('auth')->except([
            'index', 'show', 'showSubscribeForm', 'storeSubscriber', 'registSubscriber', 'deleteSubscriber', 'preview'
        ]);
    }

    public function create() {
        $newsletter = new Newsletter;
        return view('newsletter.create', ['newsletter' => $newsletter]);
    }

    public function store(Request $request) {
        $request->validate([
            'subject' => 'required',
            'content' => 'required',
        ]);

        $newsletter = new Newsletter;
        $newsletter->subject = $request->input('subject');
        $newsletter->content = str_replace('<h2>', '<h2 style="display:block;margin:0;padding:0;color:#202020;font-size:20px;font-style:italic;font-weight:bold;line-height:20px;letter-spacing:normal;">', $request->input('content'));

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $file = $request->file('image');
            $name = time() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/newsletter', $name);
            $newsletter->image = 'storage/newsletter/' . $name;
        }

        $newsletter->save();
        return redirect()->route('newsletter');
    }

    public function edit($id) {
        $newsletter = Newsletter::findOrFail($id);
        return view('newsletter.edit', ['newsletter' => $newsletter]);
    }

    public function update($id, Request $request) {
        $newsletter = Newsletter::findOrFail($id);

        $validator = [
            'subject' => 'required',
            'content' => 'required',
        ];

        foreach($newsletter->sections as $section) {
            $validator['section'.$section->id] = 'required';
            $validator['seq'.$section->id] = 'required';
        }

        $request->validate($validator);

        foreach($newsletter->sections as $section) {
            $section->content = str_replace('<h2>', '<h2 style="display:block;margin:0;padding:0;color:#202020;font-size:20px;font-style:italic;font-weight:bold;line-height:125%;letter-spacing:normal;">', $request->input('section'.$section->id));
            $section->seq = $request->input('seq'.$section->id);
            if ($request->hasFile('image'.$section->id) && $request->file('image'.$section->id)->isValid()){
                $file = $request->file('image'.$section->id);
                $imageName = bin2hex(random_bytes(10)) . '.' . $file->getClientOriginalExtension();
                if($section->image){
                    File::delete(public_path($section->image));
                }
                $file->storeAs('public/newsletter', $imageName);
                $section->image = 'storage/newsletter/' . $imageName;
            }
            $section->save();
        }

        $newsletter->subject = $request->input('subject');
        $newsletter->content = str_replace('<h2>', '<h2 style="display:block;margin:0;padding:0;color:#202020;font-family:Playfair,Georgia,Times New Roman,serif;font-size:20px;font-style:italic;font-weight:bold;line-height:125%;letter-spacing:normal;">', $request->input('content'));

        if ($request->hasFile('image') && $request->file('image')->isValid()){
            $file = $request->file('image');
            $imageName = bin2hex(random_bytes(10)) . '.' . $file->getClientOriginalExtension();
            if($newsletter->image){
                File::delete(public_path($newsletter->image));
            }
            $file->storeAs('public/newsletter', $imageName);
            $newsletter->image = 'storage/newsletter/' . $imageName;
        }

        $newsletter->save();
        return redirect()->route('newsletter-show', ['id' => $id]);
    }

    public function createSection($id, Request $request) {
        $newsletter = Newsletter::findOrFail($id);
        $seq = $request->input('seq');
        $section = new NewsletterSection;
        $section->newsletter_id = $newsletter->id;
        $section->seq = $seq;
        $section->content = '';
        $section->save();
        $html = view('partials.newsletter.section', ['section' => $section, 'newsletter' => $newsletter])->render();
        return response()->json(['success' => true, 'html' => $html]);
    }

    public function deleteSection($newsletterID, $sectionID) {
        $newsletter = Newsletter::findOrFail($newsletterID);
        $section = $newsletter->sections->find($sectionID);
        if($section){
            if($section->image){
                File::delete(public_path($section->image));
            }
            $section->delete();
        }
        return response()->json(['success' => true]);
    }

    public function confirmDelete($id) {
        $newsletter = Newsletter::findOrFail($id);
        return view('newsletter.delete', ['newsletter' => $newsletter]);
    }

    public function delete($id, Request $request) {
        if($request->input('confirm') === 'yes') {
            $newsletter = Newsletter::findOrFail($id);
            if($newsletter->image){
                File::delete(public_path($newsletter->image));
            }
            NewsletterSection::where('newsletter_id', $newsletter->id)->delete();
            $newsletter->delete();
        }
        return redirect()->route('newsletter');
    }

    public function confirmSend($id) {
        $newsletter = Newsletter::findOrFail($id);
        return view('newsletter.send', ['newsletter' => $newsletter]);
    }

    public function confirmTest($id) {
        $newsletter = Newsletter::findOrFail($id);
        return view('newsletter.test', ['newsletter' => $newsletter]);
    }

    public function send($id, Request $request) {
        $newsletter = Newsletter::findOrFail($id);
        $users = NewsletterSubscriber::where('confirmed', true)->get();
        if($request->input('confirm') === 'yes' && !$newsletter->sent) {
            foreach($users as $user) {
                Mail::to($user->email)->send(new NewsletterMailable($newsletter, $user));
            }
            $newsletter->sent = true;
            $newsletter->save();
        }
        return redirect()->route('newsletter');
    }

    public function sendToMe($id, Request $request) {
        $newsletter = Newsletter::findOrFail($id);
        if($request->input('confirm') === 'yes') {
            if(Auth::check()){
                Mail::to(Auth::user()->email)->send(new NewsletterMailable($newsletter, Auth::user()));
            }
        }
        return redirect()->route('newsletter');
    }

    public function index() {
        $newsletters = Newsletter::all();
        return view('newsletter.list', ['newsletters' => $newsletters]);
    }

    public function showSubscribeForm() {
        return view('newsletter.subscribe');
    }

    public function show($id) {
        $newsletter = Newsletter::findOrFail($id);
        return view('newsletter.show', ['newsletter' => $newsletter]);
    }

    public function preview($id) {
        $newsletter = Newsletter::findOrFail($id);
        return view('newsletter.emails.html', ['newsletter' => $newsletter]);
    }

    public function storeSubscriber(Request $request) {
        $request->validate([
            'email' => 'required|email',
        ]);

        $regist = NewsletterSubscriber::where('email', '=', $request->input('email'))->first();
        if ($regist === null) {
            $userregist = new NewsletterSubscriber;
            $userregist->email = $request->input('email');
            $token = bin2hex(random_bytes(15));
            $userregist->remember_token = $token;

            Mail::to($userregist->email)->send(new SubscriberConfirmation($userregist));

            $userregist->created_at = \Carbon\Carbon::now();
            $userregist->save();

            $text = "Foi-lhe enviado um email para concluir o registo na newsletter.";
            return view('layouts.notification-window', ['text' => $text]);
        }
        if($regist != null && $regist->confirmed == '0'){
            $token = bin2hex(random_bytes(15));
            $regist->remember_token = $token;
            Mail::to($regist->email)->send(new SubscriberConfirmation($regist));
            $regist->created_at = \Carbon\Carbon::now();
            $regist->save();
            $text = "Foi-lhe enviado um email para concluir o registo na newsletter.";
            return view('layouts.notification-window', ['text' => $text]);
        }else{
            $text = "Não foi possível registar esse e-mail na newsletter visto que já foi enviado e-mail de confirmação de e-mail.";
            return view('layouts.notification-window', ['text' => $text]);
        }
    }

    public function registSubscriber($token){
        $regist = NewsletterSubscriber::where('remember_token', '=', $token)->first();
        if($regist === null || $regist->confirmed =='1'){
            $text = "Link Inválido. Não foi possível confirmar o registo ou o mesmo ja foi confirmado.";
            return view('layouts.notification-window', ['text' => $text]);
        }else{
            $regist->confirmed = 1;
            $regist->save();
            $text = "O seu e-mail foi registado com sucesso na nossa newsletter! Sempre que houverem novidades será notificado no seu e-mail.";
            return view('layouts.notification-window', ['text' => $text]);
        }
    }

    public function deleteSubscriber($token){
        $regist = NewsletterSubscriber::where('remember_token', '=', $token)->first();
        if($regist === null || $regist->confirmed =='0'){
            $text = "Link Inválido. Não foi possível eliminar subscrição ou a mesma já não existe.";
            return view('layouts.notification-window', ['text' => $text]);
        }else{
            $regist->delete();
            $text = "A sua subscrição foi cancelada com sucesso.";
            return view('layouts.notification-window', ['text' => $text]);
        }
    }
}
