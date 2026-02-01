<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Document;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $fileNames = [
             'Service Agreement.pdf',
            'Behavior Support Plan.pdf',
            'Client Consent Form.pdf',
            'Employment Contract.pdf',
            'Confidentiality Agreement.pdf',
            'Payment Authorization.pdf',
            'Privacy Policy.pdf',
            'Terms and Conditions.pdf',
            'Medical Release Form.pdf',
            'Vendor Agreement.pdf',
            'Partnership Agreement.pdf',
            'Non-Disclosure Agreement.pdf',
            'Healthcare Proxy.pdf',
            'Model Release Form.pdf',
            'Invoice.pdf',
            'W-9 Form.pdf',
            'Purchase Order.pdf',
            'Lease Agreement.pdf',
            'Employment Application.pdf',
            'Promissory Note.pdf',
        ];

        return [
            'name' => $this->faker->randomElement($fileNames),
            'path' => $this->faker->filePath(),
            'expires_at' => Carbon::now()->addDays(rand(-90, 90)),
            'owner_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the document is expired
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => Carbon::now()->subDays(rand(1, 90)),
        ]);
    }

    /**
     * Indicate that the document is expiring soon (within 7 days)
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => Carbon::now()->addDays(rand(1, 7)),
        ]);
    }

    /**
     * Indicate that the document is archived
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => Carbon::now(),
        ]);
    }
}
