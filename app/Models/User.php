<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function pets()
    {
        return $this->hasMany(Pet::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function follows()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'followee_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function activities()
    {
        return $this->hasMany(UserActivity::class);
    }

    public function logs()
    {
        return $this->hasMany(UserLog::class);
    }

    public function unfollow(User $user)
    {
        return $this->followings()->detach($user->id);
    }

    public function follow(User $userToFollow)
    {
        return $this->follows()->save($userToFollow);
    }

    public function isFollowing(User $user)
    {
        return $this->follows()->where('followee_id', $user->id)->exists();
    }
    public function ownsPet(User $user, Pet $pet)
    {
        return $user->id === $pet->user_id;
    }
}