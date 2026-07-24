<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContactDeliveryStatus;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class ContactController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize(Permission::ViewContact->value);

        $search = Str::substr($request->string('search')->trim()->toString(), 0, 100);

        return Inertia::render('admin/contact/index', [
            'contactSubmissions' => fn () => $this->contactSubmissions($search),
            'filters' => ['search' => $search],
            'summary' => fn (): array => [
                'total' => ContactSubmission::query()->count(),
                'delivered' => ContactSubmission::query()
                    ->where('delivery_status', ContactDeliveryStatus::Sent)
                    ->count(),
                'followUpNeeded' => ContactSubmission::query()
                    ->whereIn('delivery_status', [
                        ContactDeliveryStatus::NotConfigured,
                        ContactDeliveryStatus::Failed,
                    ])
                    ->count(),
            ],
        ]);
    }

    public function show(ContactSubmission $contactSubmission): Response
    {
        Gate::authorize(Permission::ViewContact->value);

        return Inertia::render('admin/contact/show', [
            'contactSubmission' => [
                'id' => $contactSubmission->id,
                'name' => $contactSubmission->name,
                'email' => $contactSubmission->email,
                'topic' => $contactSubmission->topic->value,
                'topicLabel' => $contactSubmission->topic->label(),
                'message' => $contactSubmission->message,
                'sourceContext' => $contactSubmission->source_context,
                'deliveryStatus' => $contactSubmission->delivery_status->value,
                'deliveryStatusLabel' => $contactSubmission->delivery_status->label(),
                'deliveryAttemptedAt' => $contactSubmission->delivery_attempted_at?->toIso8601String(),
                'deliveredAt' => $contactSubmission->delivered_at?->toIso8601String(),
                'deliveryError' => $contactSubmission->delivery_error,
                'consentedAt' => $contactSubmission->consented_at->toIso8601String(),
                'createdAt' => $contactSubmission->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, covariant array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     topicLabel: string,
     *     messageExcerpt: string,
     *     deliveryStatus: string,
     *     deliveryStatusLabel: string,
     *     createdAt: string
     * }>
     */
    private function contactSubmissions(string $search): LengthAwarePaginator
    {
        return ContactSubmission::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $searchPattern = '%'.Str::lower($search).'%';

                $query->where(function (Builder $query) use ($searchPattern): void {
                    $query
                        ->whereRaw('LOWER(name) LIKE ?', [$searchPattern])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$searchPattern])
                        ->orWhereRaw('LOWER(message) LIKE ?', [$searchPattern]);
                });
            })
            ->latest()
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (ContactSubmission $contactSubmission): array => [
                'id' => $contactSubmission->id,
                'name' => $contactSubmission->name,
                'email' => $contactSubmission->email,
                'topicLabel' => $contactSubmission->topic->label(),
                'messageExcerpt' => Str::limit($contactSubmission->message, 120),
                'deliveryStatus' => $contactSubmission->delivery_status->value,
                'deliveryStatusLabel' => $contactSubmission->delivery_status->label(),
                'createdAt' => $contactSubmission->created_at->toIso8601String(),
            ]);
    }
}
