<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    const ADMIN_TYPE = 'admin';
    const FINANCE_TYPE = 'finance';
    const MANAGER_TYPE = 'manager';
    const WAITER_TYPE = 'waiter';
    const EMPLOYEE_TYPE = 'employee';
    const KITCHEN_TYPE = 'kitchen';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function isAdmin()    {
        return $this->type === self::ADMIN_TYPE;
    }

    public function isFinance(){
        return

            $this->type === self::FINANCE_TYPE OR
            $this->type === self::ADMIN_TYPE;
    }

    public function isManager()    {
        return
            $this->type === self::FINANCE_TYPE OR
            $this->type === self::MANAGER_TYPE OR
            $this->type === self::ADMIN_TYPE;
    }

    public function isWaiter()    {
        return
            $this->type === self::FINANCE_TYPE OR
            $this->type === self::WAITER_TYPE OR
            $this->type === self::MANAGER_TYPE OR
            $this->type === self::ADMIN_TYPE;
    }

    public function isEmployee(){
        return
            $this->type === self::EMPLOYEE_TYPE OR
            $this->type === self::KITCHEN_TYPE OR
            $this->type === self::FINANCE_TYPE OR
            $this->type === self::WAITER_TYPE OR
            $this->type === self::MANAGER_TYPE OR
            $this->type === self::ADMIN_TYPE;

    }

    /**
     * Kitchen staff: reach the private area and the kitchen display only.
     */
    public function isKitchen(){
        return
            $this->type === self::KITCHEN_TYPE OR
            $this->type === self::MANAGER_TYPE OR
            $this->type === self::ADMIN_TYPE;
    }
}
