<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * @test
     */
    public function FindHotels()
    {
        $response = $this->get('/api/find_hotels?city=aac&from_date=2020-07-24&to_date=2020-08-10&adults_number=2');

        $response->assertStatus(200)
        ->assertJson([
            'result' => true,
        ]);
    }

    /**
     * @test
     */
    public function BestHotel()
    {
        $response = $this->get('/api/best_hotel?city=aac&fromDate=2020-07-25&toDate=2020-08-01&numberOfAdults=1');

        $response->assertStatus(200)
        ->assertJson([]);
    }


    /**
     * @test
     */
    public function TopHotel()
    {
        $response = $this->get('/api/top_hotel?city=aac&from=2020-07-24&To=2020-08-10&adultsCount=2');

        $response->assertStatus(200)
        ->assertJson([]);
    }
}
