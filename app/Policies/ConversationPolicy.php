<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConversationPolicy
{

    use HandlesAuthorization;   
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Conversation $conversation)
    {
        // L'utilisateur peut voir la conversation s'il est le customer
        // ou s'il est le merchant associé (via merchant->user_id)
        return $user->id === $conversation->customer_id 
            || ($conversation->merchant && $conversation->merchant->user_id === $user->id);
    }

    public function sendMessage(User $user, Conversation $conversation)
    {
        return $this->view($user, $conversation);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Conversation $conversation): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Conversation $conversation): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Conversation $conversation): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Conversation $conversation): bool
    {
        //
    }
}
