<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Built-in fallback assistant (keyword search over FAQs and the catalogue). It is NOT an AI model.
 * If CHATBASE_BOT_ID is configured the layout embeds Chatbase instead (as in the reference) and this endpoint is unused.
 */
class ChatController extends Controller
{
    public function ask(Request $request)
    {
        $text = mb_strtolower(trim($request->validate(['message' => ['required', 'string', 'max:300']])['message']));
        $words = collect(preg_split('/\W+/u', $text))->filter(fn ($w) => mb_strlen($w) >= 3)->unique()->take(6);

        $links = [];
        $reply = null;
        if ($words->isNotEmpty()) {
            $faq = Faq::where('is_active', true)->whereNotNull('answer')->where(function ($q) use ($words) {
                foreach ($words as $w) { $q->orWhere('question', 'like', '%'.addcslashes($w, '%_\\').'%'); }
            })->first();
            if ($faq) {
                $reply = $faq->answer;
                $links[] = ['label' => 'Help Centre', 'url' => route('help')];
            }
            $products = Product::published()->where(function ($q) use ($words) {
                foreach ($words as $w) { $q->orWhere('name', 'like', '%'.addcslashes($w, '%_\\').'%'); }
            })->limit(3)->get();
            foreach ($products as $p) {
                $links[] = ['label' => $p->name, 'url' => route('product', $p)];
            }
            if (! $reply && $products->isNotEmpty()) {
                $reply = 'Here are some products that match what you asked:';
            }
        }
        return response()->json([
            'reply' => $reply ?? 'Sorry, I could not find an answer to that. Please visit our Help Centre or contact us.',
            'links' => $links ?: [['label' => 'Contact us', 'url' => route('contact')]],
        ]);
    }
}
