<?php

namespace App\Tests\Security\Voter;

use App\Entity\Comment;
use App\Entity\User;
use App\Security\Voter\CommentVoter; // This will cause error if not exists
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class CommentVoterTest extends TestCase
{
    private CommentVoter $voter;
    private RoleHierarchyInterface $roleHierarchy;

    protected function setUp(): void
    {
        $this->roleHierarchy = $this->createMock(RoleHierarchyInterface::class);
        $this->roleHierarchy->method('getReachableRoleNames')
            ->willReturnCallback(function (array $roles) {
                // Simple mock implementation of hierarchy for testing
                if (in_array('ROLE_SUPER_ADMIN', $roles)) {
                    return array_unique(array_merge($roles, ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_CREATOR_ADMIN']));
                }
                if (in_array('ROLE_ADMIN', $roles)) {
                    return array_unique(array_merge($roles, ['ROLE_USER', 'ROLE_CREATOR_ADMIN']));
                }
                return $roles;
            });

        $this->voter = new CommentVoter($this->roleHierarchy);
    }

    public function testVoteOnAttributeDeleteGrantedForOwner(): void
    {
        $user = new User();
        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($user, 1);

        $comment = new Comment();
        $comment->setUser($user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $comment, ['DELETE']);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testVoteOnAttributeDeleteGrantedForSuperAdmin(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_SUPER_ADMIN']);
        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($user, 1);

        $comment = new Comment();
        $otherUser = new User();
        $property->setValue($otherUser, 2);
        $comment->setUser($otherUser); // Different user

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $comment, ['DELETE']);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testVoteOnAttributeDeleteGrantedForAdmin(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($user, 1);

        $comment = new Comment();
        $otherUser = new User();
        $property->setValue($otherUser, 2);
        $comment->setUser($otherUser); // Different user

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $comment, ['DELETE']);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testVoteOnAttributeDeleteDeniedForOthers(): void
    {
        $user = new User();
        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($user, 1);

        $comment = new Comment();
        $otherUser = new User();
        $property->setValue($otherUser, 2);
        $comment->setUser($otherUser); // Different user

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $comment, ['DELETE']);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testVoteOnAttributeDeleteDeniedForAnonymous(): void
    {
        $comment = new Comment();
        $comment->setUser(new User());

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, $comment, ['DELETE']);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }
}
