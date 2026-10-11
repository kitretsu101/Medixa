<?php

namespace App\Http\Controllers;

use App\Models\MedicalKnowledgeArticle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminKnowledgeController extends Controller
{
    public function index(): View
    {
        return view('admin.knowledge.index', [
            'articles' => MedicalKnowledgeArticle::query()->orderBy('topic')->orderBy('language')->get(),
            'reviewDays' => (int) config('medixa.knowledge.review_valid_days', 365),
        ]);
    }

    public function edit(MedicalKnowledgeArticle $article): View
    {
        return view('admin.knowledge.review', [
            'article' => $article,
        ]);
    }

    public function update(Request $request, MedicalKnowledgeArticle $article): RedirectResponse
    {
        $validated = $request->validate([
            'approval_status' => ['required', 'in:pending,approved,rejected'],
            'reviewer_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn (): bool => in_array($request->input('approval_status'), ['approved', 'rejected'], true)),
            ],
            'is_active' => ['required', 'boolean'],
        ]);

        $status = $validated['approval_status'];
        $reviewer = trim((string) ($validated['reviewer_name'] ?? ''));

        $article->update([
            'approval_status' => $status,
            'reviewer_name' => $reviewer !== '' ? $reviewer : null,
            'is_active' => (bool) $validated['is_active'],
            'review_date' => in_array($status, ['approved', 'rejected'], true) ? now()->toDateString() : null,
        ]);

        return redirect()
            ->route('admin.knowledge.index')
            ->with('success', 'Review decision recorded. Medixa does not certify the reviewer\'s qualification.');
    }
}
