<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any documents.
     * Users can only view their own documents.
     */
    public function viewAny(User $user): bool
    {
        // User must be authenticated to view documents
        return $user !== null;
    }

    /**
     * Determine if the user can view the document.
     */
    public function view(User $user, Document $document): bool
    {
        return $user->id === $document->owner_id;
    }

    /**
     * Determine if the user can create documents.
     */
    public function create(User $user): bool
    {
        // User must be authenticated to create documents
        return $user !== null;
    }

    /**
     * Determine if the user can update the document.
     */
    public function update(User $user, Document $document): bool
    {
        return $user->id === $document->owner_id;
    }

    /**
     * Determine if the user can delete the document.
     */
    public function delete(User $user, Document $document): bool
    {
        return $user->id === $document->owner_id;
    }

    /**
     * Determine if the user can restore the document.
     */
    public function restore(User $user, Document $document): bool
    {
        return $user->id === $document->owner_id;
    }

    /**
     * Determine if the user can permanently delete the document.
     */
    public function forceDelete(User $user, Document $document): bool
    {
        return $user->id === $document->owner_id;
    }
}
