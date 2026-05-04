<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ServiceOrder;
use App\Models\ServiceOrderPhoto;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceOrderPhoto>
 */
final class ServiceOrderPhotoFactory extends Factory
{
    protected $model = ServiceOrderPhoto::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'service_order_id' => ServiceOrder::factory(),
            'user_id' => User::factory(),
            'disk' => 'local',
            'path' => 'tenants/1/service_orders/uuid/photo.jpg',
            'original_filename' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024 * 500, // 500 KB
            'uploaded_at' => now(),
            'version' => 1,
            'last_modified_by_device' => null,
        ];
    }
}
