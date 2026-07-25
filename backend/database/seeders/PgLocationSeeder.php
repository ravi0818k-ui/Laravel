<?php

namespace Database\Seeders;

use App\Models\PgLocation;
use Illuminate\Database\Seeder;

/**
 * Seeds pg_locations from existing pg-data.json so the public API
 * returns data immediately after setup.
 */
class PgLocationSeeder extends Seeder
{
    public function run(): void
    {
        $pgData = [
            [
                'name' => 'PG A1 – Shanti Nagar',
                'address' => 'F22F+QF8, Shanti Nagar, Gurugram, Haryana',
                'city' => 'Gurugram',
                'state' => 'Haryana',
                'pincode' => '122001',
                'latitude' => 28.4524,
                'longitude' => 77.0246,
                'tenant_id_prefix' => 'TSN',
                'contact_mobile' => '919310226604',
                'starting_rent' => 8000,
                'photos' => [
                    'images/main photo PG A1.jpeg',
                    'images/First.jpeg',
                    'images/main side view.jpeg',
                    'images/room photo.jpeg',
                    'images/room photo hall.jpeg',
                    'images/room photo tv.jpeg',
                    'images/single room.jpeg',
                    'images/bigger room.jpeg',
                    'images/another bigger room.jpeg',
                    'images/another bigger room hall.jpeg',
                ],
                'metadata' => [
                    'slug' => 'shanti-nagar',
                    'security_deposit' => 3000,
                    'sharing_type' => 'Double Sharing',
                    'whatsapp' => '919310226604',
                    'phone_display' => '+91 93102 26604',
                    'map_iframe' => 'https://www.google.com/maps/embed?pb=!1m17!1m12!1m3!1d3507.899991090441!2d77.024633!3d28.452430999999994!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m2!1m1!2zMjjCsDI3JzA4LjgiTiA3N8KwMDEnMjguNyJF!5e0!3m2!1sen!2sin!4v1775989365387!5m2!1sen!2sin',
                    'map_link' => 'https://maps.app.goo.gl/bF6cMRxsDNpSt6Ld7',
                    'videos' => ['video.mp4'],
                    'amenities' => ['AC', 'Geyser', 'Wi-Fi', 'Washing Machine', 'Double Bed', 'Wardrobe', 'Meals Included'],
                    'meals' => 'Breakfast + Dinner daily | Sat & Sun: All 3 meals',
                    'tags' => ['AC', 'Food', 'Wi-Fi'],
                ],
            ],
            [
                'name' => 'PG A1 – Jharsa Village',
                'address' => 'Jharsa Village, Gurugram, Haryana',
                'city' => 'Gurugram',
                'state' => 'Haryana',
                'pincode' => '122003',
                'latitude' => 28.4434,
                'longitude' => 77.0509,
                'tenant_id_prefix' => 'TJV',
                'contact_mobile' => '919310226604',
                'starting_rent' => 9000,
                'photos' => [
                    'PGA1 jharsa village-done/jharsa village new 1.jpeg',
                    'PGA1 jharsa village-done/jharsa village new 4 bedroom.jpeg',
                    'PGA1 jharsa village-done/jharsa village new 6 bedroom.jpeg',
                    'PGA1 jharsa village-done/harsa village new 7 balcony.jpeg',
                    'PGA1 jharsa village-done/jharsa village new 8 washroom.jpeg',
                    'PGA1 jharsa village-done/jharsa village new 9 terrace.jpeg',
                ],
                'metadata' => [
                    'slug' => 'jharsa-village',
                    'security_deposit' => 5000,
                    'sharing_type' => 'Double Sharing',
                    'whatsapp' => '919310226604',
                    'phone_display' => '+91 93102 26604',
                    'map_iframe' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3508.1979645257093!2d77.05094520000002!3d28.443448199999995!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x390d19403fc7af29%3A0x4d97571a0c52791f!2sPG-A1!5e0!3m2!1sen!2sin!4v1782483859374!5m2!1sen!2sin',
                    'map_link' => 'https://maps.google.com/?q=28.443448,77.050945',
                    'videos' => [],
                    'amenities' => ['AC', 'Geyser', 'Wi-Fi', 'Washing Machine', 'Double Bed', 'Wardrobe', 'Meals Included'],
                    'meals' => '',
                    'tags' => ['AC', 'Food', 'Wi-Fi'],
                ],
            ],
            [
                'name' => 'PG A1 – Sector 46',
                'address' => 'Sector 46, Gurugram, Haryana',
                'city' => 'Gurugram',
                'state' => 'Haryana',
                'pincode' => '122003',
                'latitude' => 28.4385,
                'longitude' => 77.0531,
                'tenant_id_prefix' => 'TS46',
                'contact_mobile' => '919310226604',
                'starting_rent' => 9000,
                'photos' => [
                    'PGA1 Sec 46-done/20260618_170501.jpg',
                    'PGA1 Sec 46-done/20260618_170512.jpg',
                    'PGA1 Sec 46-done/20260618_170551.jpg',
                    'PGA1 Sec 46-done/20260618_171622.jpg',
                    'PGA1 Sec 46-done/20260618_171637.jpg',
                ],
                'metadata' => [
                    'slug' => 'sector-46',
                    'security_deposit' => 5000,
                    'sharing_type' => 'Double Sharing',
                    'whatsapp' => '919310226604',
                    'phone_display' => '+91 93102 26604',
                    'map_iframe' => 'https://www.google.com/maps/embed?pb=!1m17!1m12!1m3!1d3508.362532240454!2d77.05307017549374!3d28.43848597577126!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m2!1m1!2zMjjCsDI2JzE4LjYiTiA3N8KwMDMnMjAuMyJF!5e0!3m2!1sen!2sin!4v1782482909905!5m2!1sen!2sin',
                    'map_link' => 'https://maps.app.goo.gl/jPy4Wq3brLKtDJ8Z7',
                    'videos' => [],
                    'amenities' => ['AC', 'Geyser', 'Wi-Fi', 'Washing Machine', 'Double Bed', 'Wardrobe', 'Meals Included'],
                    'meals' => '',
                    'tags' => ['AC', 'Food', 'Wi-Fi'],
                ],
            ],
            [
                'name' => 'PG A1 – Saraswati Vihar',
                'address' => 'Saraswati Vihar, Gurugram, Haryana',
                'city' => 'Gurugram',
                'state' => 'Haryana',
                'pincode' => '122002',
                'latitude' => 28.4758,
                'longitude' => 77.0830,
                'tenant_id_prefix' => 'TSV',
                'contact_mobile' => '919310226604',
                'starting_rent' => 8000,
                'photos' => [
                    'PGA1 Sarswati Vihar PG-done/20260617_194010.jpg',
                    'PGA1 Sarswati Vihar PG-done/20260617_192612.jpg',
                    'PGA1 Sarswati Vihar PG-done/20260617_192627.jpg',
                    'PGA1 Sarswati Vihar PG-done/20260617_192631.jpg',
                    'PGA1 Sarswati Vihar PG-done/20260617_192641.jpg',
                ],
                'metadata' => [
                    'slug' => 'saraswati-vihar',
                    'security_deposit' => 5000,
                    'sharing_type' => 'Double Sharing',
                    'whatsapp' => '919310226604',
                    'phone_display' => '+91 93102 26604',
                    'map_iframe' => 'https://www.google.com/maps/embed?pb=!1m17!1m12!1m3!1d3507.1248659476482!2d77.08295!3d28.475786!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m2!1m1!2zMjjCsDI4JzMyLjgiTiA3N8KwMDQnNTguNiJF!5e0!3m2!1sen!2sin!4v1782482985339!5m2!1sen!2sin',
                    'map_link' => 'https://maps.google.com/?q=28.475786,77.08295',
                    'videos' => [
                        'https://drive.google.com/file/d/1E9DNX18jm3DvdJSCFiAbuL_LqDujLGRl/preview',
                        'https://drive.google.com/file/d/1r44Fjq7us0CNn5xQLPu8nutGItoZ8Fti/preview',
                    ],
                    'amenities' => ['AC', 'Geyser', 'Wi-Fi', 'Washing Machine', 'Double Bed', 'Wardrobe', 'Meals Included'],
                    'meals' => '',
                    'tags' => ['AC', 'Food', 'Wi-Fi'],
                ],
            ],
            [
                'name' => 'PG A1 – Saraswati Vihar (1BHK)',
                'address' => 'Saraswati Vihar, Gurugram, Haryana',
                'city' => 'Gurugram',
                'state' => 'Haryana',
                'pincode' => '122002',
                'latitude' => 28.4758,
                'longitude' => 77.0830,
                'tenant_id_prefix' => 'TSV1',
                'contact_mobile' => '919310226604',
                'starting_rent' => 22000,
                'photos' => [
                    'PGA1 1BHK saraswati vihar-done/PGA1 1BHk saraswati vihar main gate-1.jpeg',
                    'PGA1 1BHK saraswati vihar-done/PGA1 1BHk saraswati vihar outside view-1.jpeg',
                    'PGA1 1BHK saraswati vihar-done/PGA1 1BHk saraswati vihar drawing room-1.jpeg',
                    'PGA1 1BHK saraswati vihar-done/PGA1 1BHk saraswati vihar Main room-1.jpeg',
                    'PGA1 1BHK saraswati vihar-done/PGA1 1BHk saraswati vihar kitchen-2.jpeg',
                    'PGA1 1BHK saraswati vihar-done/PGA1 1BHk saraswati vihar bathroom-1.jpeg',
                ],
                'metadata' => [
                    'slug' => 'saraswati-vihar-1bhk',
                    'security_deposit' => 22000,
                    'sharing_type' => '1BHK',
                    'whatsapp' => '919310226604',
                    'phone_display' => '+91 93102 26604',
                    'map_iframe' => 'https://www.google.com/maps/embed?pb=!1m17!1m12!1m3!1d3507.1248659476482!2d77.08295!3d28.475786!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m2!1m1!2zMjjCsDI4JzMyLjgiTiA3N8KwMDQnNTguNiJF!5e0!3m2!1sen!2sin!4v1782482985339!5m2!1sen!2sin',
                    'map_link' => 'https://maps.google.com/?q=28.475786,77.08295',
                    'videos' => [
                        'https://drive.google.com/file/d/1JN0sUZ3wMlE0QgAJywWbYRaAfeMMq2M_/preview',
                    ],
                    'amenities' => ['AC', 'Geyser', 'Wi-Fi', 'Washing Machine', 'Double Bed', 'Wardrobe', 'Kitchen', 'Drawing Room'],
                    'meals' => '',
                    'tags' => ['AC', 'Wi-Fi', 'Kitchen', '1BHK'],
                ],
            ],
        ];

        foreach ($pgData as $data) {
            PgLocation::firstOrCreate(
                ['tenant_id_prefix' => $data['tenant_id_prefix']],
                $data
            );
        }
    }
}
