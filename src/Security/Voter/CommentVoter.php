<?php

namespace App\Security\Voter;

use App\Entity\Comment;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class CommentVoter extends Voter
{
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    public function __construct(private RoleHierarchyInterface $roleHierarchy)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE])
            && $subject instanceof Comment;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Comment $comment */
        $comment = $subject;

        return match($attribute) {
            self::EDIT, self::DELETE => $this->canEdit($comment, $user),
            default => false,
        };
    }

    private function canEdit(Comment $comment, User $user): bool
    {
        // Owner check
        if ($comment->getUser() === $user) {
            return true;
        }

        // Admin check using hierarchy
        $roles = $user->getRoles();
        $reachableRoles = $this->roleHierarchy->getReachableRoleNames($roles);

        return in_array('ROLE_ADMIN', $reachableRoles);
    }
}
