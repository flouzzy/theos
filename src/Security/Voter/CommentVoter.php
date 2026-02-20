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
    public const DELETE = 'DELETE';

    public function __construct(private RoleHierarchyInterface $roleHierarchy)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::DELETE
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

        // 1. Check if owner
        if ($comment->getUser()->getId() === $user->getId()) {
            return true;
        }

        // 2. Check if admin
        // We use reachable roles to account for hierarchy (e.g. SUPER_ADMIN implies ADMIN)
        $roles = $this->roleHierarchy->getReachableRoleNames($user->getRoles());

        if (in_array('ROLE_ADMIN', $roles) || in_array('ROLE_SUPER_ADMIN', $roles)) {
            return true;
        }

        return false;
    }
}
