<?php

namespace Tests\Feature;

use App\Models\Spatie\Team;
use App\Models\Spatie\User;
use App\Models\StockProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_a_stock_product(): void
    {
        $user = User::create([
            'name' => 'Admin Estoque',
            'email' => 'admin-stock@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $team = new Team([
            'name' => 'EQUIPE ESTOQUE',
            'personal_team' => false,
        ]);
        $team->user_id = $user->id;
        $team->save();
        $user->teams()->attach($team->id, ['role' => 'Admin']);
        $user->forceFill(['current_team_id' => $team->id])->save();

        $stock = StockProduct::create([
            'product_name' => 'Produto para excluir',
            'quantity' => 2,
            'cost_price' => 10,
            'price' => 15,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->delete(route('stock.destroy', ['stock' => $stock->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('stock_products', ['id' => $stock->id]);
    }
}
