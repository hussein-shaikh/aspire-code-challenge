<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\RoleModel;
use App\Models\User;
use App\Models\UserRoleMappingModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        RoleModel::insert([
            [
                'id' => 1,
                'name' => "user",
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")

            ],
            [
                'id' => 2,
                'name' => "admin",
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ]
        ]);


        $uuidGen = Str::uuid();
        User::create([
            "id" => $uuidGen,
            "email" => "admin@aspire.com",
            "password" => Hash::make("123123"),
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s"),
            "is_active" => 1,
            "name" => "Panel Admin"
        ]);

        UserRoleMappingModel::create(
            [
                "user_id" => $uuidGen,
                "role_id" => 2,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s"),
                "is_active" => 1,
            ]
        );
    }
}
