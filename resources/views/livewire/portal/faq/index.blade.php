<?php

use App\Domain\Content\Models\Faq;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    public function with(): array
    {
        return [
            'faqs' => Faq::query()->where('is_active', true)->orderBy('sort_order')->get()->groupBy(fn ($f) => $f->category ?: 'General'),
        ];
    }
}; ?>

<div class="bg-stone-100 pt-24 pb-20">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <h1 class="font-display text-4xl text-stone-900">{{ __('portal.faq.title') }}</h1>
        <div class="mt-10 space-y-8">
            @forelse($faqs as $category => $items)
                <section>
                    <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-800">{{ $category }}</h2>
                    <div class="mt-4 space-y-3">
                        @foreach($items as $faq)
                            <details class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                                <summary class="cursor-pointer font-semibold text-stone-900">{{ $faq->question }}</summary>
                                <p class="mt-3 text-stone-600 leading-relaxed">{{ $faq->answer }}</p>
                            </details>
                        @endforeach
                    </div>
                </section>
            @empty
                <p class="text-stone-500">No FAQs yet.</p>
            @endforelse
        </div>
    </div>
</div>
