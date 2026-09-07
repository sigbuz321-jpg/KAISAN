<?php

namespace Database\Seeders;

use App\Enums\SchoolLevel;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CurriculumMerdekaSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::subjects() as $subjectData) {
            $subject = Subject::updateOrCreate(
                ['slug' => Str::slug($subjectData['name'].'-'.$subjectData['school_level']->value)],
                [
                    'name' => $subjectData['name'],
                    'school_level' => $subjectData['school_level'],
                    'start_grade' => $subjectData['start_grade'],
                    'end_grade' => $subjectData['end_grade'],
                    'is_active' => true,
                ],
            );

            foreach ($subjectData['topics'] as $order => $topic) {
                Topic::updateOrCreate(
                    ['subject_id' => $subject->id, 'name' => $topic],
                    ['order' => $order],
                );
            }
        }
    }

    /**
     * Kurikulum Merdeka SD dan SMP dengan topik representatif per mapel.
     *
     * @return array<int, array{name: string, school_level: SchoolLevel, start_grade: int, end_grade: int, topics: list<string>}>
     */
    public static function subjects(): array
    {
        return [
            // ===== SD (Kelas 1-6) =====
            [
                'name' => 'Pendidikan Agama Islam',
                'school_level' => SchoolLevel::SD,
                'start_grade' => 1,
                'end_grade' => 6,
                'topics' => [
                    'Akidah (Keimanan)',
                    'Akhlak (Perilaku Mulia)',
                    'Ibadah (Tata Cara Beribadah)',
                    'Al-Qur\'an dan Hadis',
                    'Sejarah Islam',
                ],
            ],
            [
                'name' => 'Pendidikan Pancasila',
                'school_level' => SchoolLevel::SD,
                'start_grade' => 1,
                'end_grade' => 6,
                'topics' => [
                    'Butir-Butir Pancasila',
                    'Nilai-Nilai Pancasila dalam Kehidupan',
                    'Identitas Nasional',
                    'Kewarganegaraan',
                    'Hak dan Kewajiban Warga Negara',
                ],
            ],
            [
                'name' => 'Bahasa Indonesia',
                'school_level' => SchoolLevel::SD,
                'start_grade' => 1,
                'end_grade' => 6,
                'topics' => [
                    'Membaca Pemahaman',
                    'Menulis Narasi',
                    'Puisi dan Sastra',
                    'Teks Prosedur',
                    'Teks Deskripsi',
                    'Komunikasi Lisan',
                ],
            ],
            [
                'name' => 'Matematika',
                'school_level' => SchoolLevel::SD,
                'start_grade' => 1,
                'end_grade' => 6,
                'topics' => [
                    'Bilangan Bulat dan Operasinya',
                    'Pecahan',
                    'Geometri dan Pengukuran',
                    'Data dan Statistika',
                    'Aljabar Dasar',
                ],
            ],
            [
                'name' => 'Ilmu Pengetahuan Alam dan Sosial (IPAS)',
                'school_level' => SchoolLevel::SD,
                'start_grade' => 1,
                'end_grade' => 6,
                'topics' => [
                    'Makhluk Hidup dan Lingkungan',
                    'Benda dan Sifatnya',
                    'Energi dan Perubahannya',
                    'Masyarakat dan Budaya',
                    'Sejarah Lokal',
                    'Geografi dan Peta',
                ],
            ],
            [
                'name' => 'Seni Budaya',
                'school_level' => SchoolLevel::SD,
                'start_grade' => 1,
                'end_grade' => 6,
                'topics' => [
                    'Seni Rupa (Melukis dan Menggambar)',
                    'Seni Musik',
                    'Seni Tari',
                    'Seni Teater',
                    'Kerajinan Tangan',
                ],
            ],
            [
                'name' => 'Pendidikan Jasmani, Olahraga, dan Kesehatan (PJOK)',
                'school_level' => SchoolLevel::SD,
                'start_grade' => 1,
                'end_grade' => 6,
                'topics' => [
                    'Gerak Dasar Atletik',
                    'Permainan Bola Kecil',
                    'Permainan Bola Besar',
                    'Senam',
                    'Kesehatan dan Kebersihan',
                    'Gizi Seimbang',
                ],
            ],
            [
                'name' => 'Bahasa Inggris',
                'school_level' => SchoolLevel::SD,
                'start_grade' => 1,
                'end_grade' => 6,
                'topics' => [
                    'Greetings (Salam)',
                    'Numbers and Colors',
                    'Daily Activities',
                    'Family and Friends',
                    'Foods and Drinks',
                    'Classroom Vocabulary',
                ],
            ],

            // ===== SMP (Kelas 7-9) =====
            [
                'name' => 'Pendidikan Agama Islam',
                'school_level' => SchoolLevel::SMP,
                'start_grade' => 7,
                'end_grade' => 9,
                'topics' => [
                    'Akidah dan Tauhid',
                    'Fikih (Hukum Islam)',
                    'Akhlak dan Tasawuf',
                    'Sejarah Peradaban Islam',
                    'Al-Qur\'an dan Tafsir',
                ],
            ],
            [
                'name' => 'Pendidikan Pancasila',
                'school_level' => SchoolLevel::SMP,
                'start_grade' => 7,
                'end_grade' => 9,
                'topics' => [
                    'Pancasila sebagai Ideologi Nasional',
                    'Demokrasi Pancasila',
                    'Hak Asasi Manusia',
                    'Supremasi Hukum',
                    'Persatuan dan Kesatuan Bangsa',
                ],
            ],
            [
                'name' => 'Bahasa Indonesia',
                'school_level' => SchoolLevel::SMP,
                'start_grade' => 7,
                'end_grade' => 9,
                'topics' => [
                    'Teks Narasi',
                    'Teks Deskripsi',
                    'Teks Eksposisi',
                    'Teks Persuasi',
                    'Puisi Modern',
                    'Drama dan Teater',
                ],
            ],
            [
                'name' => 'Matematika',
                'school_level' => SchoolLevel::SMP,
                'start_grade' => 7,
                'end_grade' => 9,
                'topics' => [
                    'Bilangan Rasional',
                    'Aljabar dan Persamaan',
                    'Geometri dan Trigonometri',
                    'Statistika dan Peluang',
                    'Fungsi dan Relasi',
                ],
            ],
            [
                'name' => 'IPA',
                'school_level' => SchoolLevel::SMP,
                'start_grade' => 7,
                'end_grade' => 9,
                'topics' => [
                    'Biologi: Sistem Organisasi Makhluk Hidup',
                    'Biologi: Ekosistem',
                    'Fisika: Gerak dan Gaya',
                    'Fisika: Energi',
                    'Kimia: Zat dan Partikel',
                    'Kimia: Reaksi Kimia',
                ],
            ],
            [
                'name' => 'Ilmu Pengetahuan Sosial (IPS)',
                'school_level' => SchoolLevel::SMP,
                'start_grade' => 7,
                'end_grade' => 9,
                'topics' => [
                    'Sejarah Nusantara Masa Lalu',
                    'Sejarah Nusantara Masa Kini',
                    'Geografi Fisik Indonesia',
                    'Geografi Sosial dan Ekonomi',
                    'Peradaban Masa Kuno',
                ],
            ],
            [
                'name' => 'Bahasa Inggris',
                'school_level' => SchoolLevel::SMP,
                'start_grade' => 7,
                'end_grade' => 9,
                'topics' => [
                    'Greeting and Introduction',
                    'Present Tense',
                    'Past Tense',
                    'Descriptive Text',
                    'Recount Text',
                    'Procedure Text',
                ],
            ],
            [
                'name' => 'Pendidikan Jasmani, Olahraga, dan Kesehatan (PJOK)',
                'school_level' => SchoolLevel::SMP,
                'start_grade' => 7,
                'end_grade' => 9,
                'topics' => [
                    'Atletik',
                    'Sepak Bola',
                    'Bola Basket',
                    'Bola Voli',
                    'Tenis Meja',
                    'Kesehatan Reproduksi',
                ],
            ],
            [
                'name' => 'Informatika',
                'school_level' => SchoolLevel::SMP,
                'start_grade' => 7,
                'end_grade' => 9,
                'topics' => [
                    'Fondasi Informatika',
                    'Algoritma Dasar',
                    'Pemrograman Sederhana',
                    'Jaringan Komputer',
                    'Keamanan Digital',
                    'Dampak Teknologi',
                ],
            ],
            [
                'name' => 'Seni Budaya',
                'school_level' => SchoolLevel::SMP,
                'start_grade' => 7,
                'end_grade' => 9,
                'topics' => [
                    'Seni Rupa Tradisional',
                    'Seni Rupa Kontemporer',
                    'Musik Tradisional',
                    'Musik Kontemporer',
                    'Tari Tradisional',
                    'Teater Klasik dan Modern',
                ],
            ],
            [
                'name' => 'Prakarya',
                'school_level' => SchoolLevel::SMP,
                'start_grade' => 7,
                'end_grade' => 9,
                'topics' => [
                    'Kerajinan dari Bahan Alam',
                    'Kerajinan dari Bahan Buatan',
                    'Pengolahan Pangan',
                    'Rekayasa Teknologi Sederhana',
                    'Kewirausahaan Pemula',
                ],
            ],
        ];
    }
}
