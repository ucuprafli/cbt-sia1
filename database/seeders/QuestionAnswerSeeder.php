<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionAnswerSeeder extends Seeder
{
    private const TOTAL_PER_SUBJECT = 150;

    public function run(): void
    {
        mt_srand(2026); // agar hasil acak konsisten setiap kali seeder dijalankan

        $map = [
            'Seni Budaya'         => 'seniBudayaQuestions',
            'Sejarah'             => 'sejarahQuestions',
            'Pengetahuan Sosial'  => 'pengetahuanSosialQuestions',
        ];

        foreach ($map as $subjectName => $method) {
            $subject = Subject::where('name', $subjectName)->first();

            if (! $subject) {
                $this->command?->warn("Mata pelajaran '{$subjectName}' tidak ditemukan, dilewati.");

                continue;
            }

            $pool = self::{$method}();
            shuffle($pool);
            $pool = array_slice($pool, 0, self::TOTAL_PER_SUBJECT);

            DB::transaction(function () use ($subject, $pool, $subjectName) {
                // Bersihkan soal lama milik pelajaran ini (jawabannya ikut terhapus via cascadeOnDelete)
                Question::withTrashed()->where('subject_id', $subject->id)->forceDelete();

                $letters = ['A', 'B', 'C', 'D'];

                foreach ($pool as $item) {
                    $correctLetter = null;
                    foreach ($item['options'] as $idx => $option) {
                        if ($option['correct']) {
                            $correctLetter = $letters[$idx];
                        }
                    }

                    $question = Question::create([
                        'subject_id'     => $subject->id,
                        'payload'        => $item['question'],
                        'correct_answer' => $correctLetter,
                        'description'    => null,
                        'is_active'      => true,
                    ]);

                    foreach ($item['options'] as $idx => $option) {
                        Answer::create([
                            'question_id' => $question->id,
                            'letter'      => $letters[$idx],
                            'text'        => $option['text'],
                            'is_active'   => true,
                        ]);
                    }
                }

                $this->command?->info("{$subjectName}: ".count($pool)." soal berhasil dibuat.");
            });
        }
    }

    // Bentuk satu soal pilihan ganda dari 1 jawaban benar + beberapa jawaban salah.
    private static function buildMcq(string $question, string $correct, array $wrongs): array
    {
        $options = [['text' => $correct, 'correct' => true]];

        foreach ($wrongs as $w) {
            $options[] = ['text' => $w, 'correct' => false];
        }

        shuffle($options);

        return ['question' => $question, 'options' => $options];
    }

    private static function pickDistractors(array $pairs, int|string $excludeKey, int $n, bool $wantValue, array $fallbackPool = []): array
    {
        $excludeValue = $wantValue ? ($pairs[$excludeKey] ?? '') : $excludeKey;
        $excludeNormalized = mb_strtolower(trim((string) $excludeValue));

        $seen = [$excludeNormalized => true];
        $result = [];

        $candidates = $pairs;
        unset($candidates[$excludeKey]);
        $keys = array_keys($candidates);
        shuffle($keys);

        foreach ($keys as $k) {
            $val = $wantValue ? (string) $candidates[$k] : (string) $k;
            $norm = mb_strtolower(trim($val));
            if (isset($seen[$norm])) {
                continue;
            }
            $seen[$norm] = true;
            $result[] = $val;
            if (count($result) >= $n) {
                break;
            }
        }

        if (count($result) < $n && ! empty($fallbackPool)) {
            $extra = $fallbackPool;
            shuffle($extra);
            foreach ($extra as $val) {
                $norm = mb_strtolower(trim($val));
                if (isset($seen[$norm])) {
                    continue;
                }
                $seen[$norm] = true;
                $result[] = $val;
                if (count($result) >= $n) {
                    break;
                }
            }
        }

        return $result;
    }

    private static function buildLookupQuestions(array $pairs, string $askValue, ?string $askKey = null, int $distractorCount = 3, array $valueFallbackPool = []): array
    {
        $items = [];

        foreach ($pairs as $key => $value) {
            $wrongValues = self::pickDistractors($pairs, $key, $distractorCount, true, $valueFallbackPool);
            // jaga-jaga: jika pool unik + fallback tetap kurang dari yang diminta, lewati soal ini
            // agar tidak menghasilkan pilihan jawaban duplikat.
            if (count($wrongValues) < $distractorCount) {
                continue;
            }
            $items[] = self::buildMcq(sprintf($askValue, $key), (string) $value, $wrongValues);

            if ($askKey !== null) {
                $wrongKeys = self::pickDistractors($pairs, $key, $distractorCount, false);
                if (count($wrongKeys) < $distractorCount) {
                    continue;
                }
                $items[] = self::buildMcq(sprintf($askKey, $value), (string) $key, $wrongKeys);
            }
        }

        return $items;
    }

    // SENI BUDAYA (pool > 150 soal berbasis data fakta, lalu diacak & diambil 150)

    private static function seniBudayaQuestions(): array
    {
        $items = [];

        // A. Alat musik tradisional & daerah asal
        $alatMusikDaerah = [
            'Angklung'          => 'Jawa Barat',
            'Sasando'           => 'Nusa Tenggara Timur',
            'Kolintang'         => 'Sulawesi Utara',
            'Gamelan'           => 'Jawa Tengah',
            'Kecapi'            => 'Jawa Barat',
            'Tifa'              => 'Papua',
            'Gordang Sambilan'  => 'Sumatera Utara',
            'Talempong'         => 'Sumatera Barat',
            'Serunai'           => 'Sumatera Barat',
            'Rebab'             => 'Jawa Tengah',
            'Bonang'            => 'Jawa Tengah',
            'Saluang'           => 'Sumatera Barat',
            'Aramba'            => 'Sumatera Utara',
            'Gambus'            => 'Riau',
            'Doll'              => 'Bengkulu',
            'Panting'           => 'Kalimantan Selatan',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $alatMusikDaerah,
            'Alat musik tradisional %s berasal dari daerah...',
            'Daerah manakah yang memiliki alat musik tradisional bernama %s?'
        ));

        // B. Tarian tradisional & daerah asal
        $tarianDaerah = [
            'Tari Saman'          => 'Aceh',
            'Tari Piring'         => 'Sumatera Barat',
            'Tari Tor-Tor'        => 'Sumatera Utara',
            'Tari Jaipong'        => 'Jawa Barat',
            'Tari Serimpi'        => 'Yogyakarta',
            'Tari Kecak'          => 'Bali',
            'Tari Pendet'         => 'Bali',
            'Tari Legong'         => 'Bali',
            'Tari Reog'           => 'Jawa Timur',
            'Tari Remo'           => 'Jawa Timur',
            'Tari Gambyong'       => 'Jawa Tengah',
            'Tari Kipas Pakarena' => 'Sulawesi Selatan',
            'Tari Maengket'       => 'Sulawesi Utara',
            'Tari Cakalele'       => 'Maluku',
            'Tari Perang'         => 'Papua',
            'Tari Zapin'          => 'Riau',
            'Tari Lego-Lego'      => 'Nusa Tenggara Timur',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $tarianDaerah,
            'Tarian tradisional %s berasal dari daerah...',
            'Daerah manakah yang memiliki tarian tradisional bernama %s?'
        ));

        // C. Lagu daerah & daerah asal
        $laguDaerah = [
            'Bungong Jeumpa'        => 'Aceh',
            'Ampar-Ampar Pisang'    => 'Kalimantan Selatan',
            'Kicir-Kicir'           => 'DKI Jakarta',
            'Injit-Injit Semut'     => 'Jambi',
            'Anak Kambing Saya'     => 'Nusa Tenggara Timur',
            'Cublak-Cublak Suweng'  => 'Jawa Tengah',
            'Manuk Dadali'          => 'Jawa Barat',
            'Yamko Rambe Yamko'     => 'Papua',
            'Rasa Sayange'          => 'Maluku',
            'O Ina Ni Keke'         => 'Sulawesi Utara',
            'Butet'                 => 'Sumatera Utara',
            'Sarinande'             => 'Maluku',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $laguDaerah,
            'Lagu daerah "%s" berasal dari...',
            'Daerah manakah yang memiliki lagu daerah berjudul "%s"?'
        ));

        // D. Rumah adat & daerah asal
        $rumahAdat = [
            'Rumah Gadang'      => 'Sumatera Barat',
            'Rumah Joglo'       => 'Jawa Tengah',
            'Rumah Limas'       => 'Sumatera Selatan',
            'Rumah Panjang'     => 'Kalimantan Barat',
            'Rumah Tongkonan'   => 'Sulawesi Selatan',
            'Rumah Honai'       => 'Papua',
            'Rumah Krong Bade'  => 'Aceh',
            'Rumah Bolon'       => 'Sumatera Utara',
            'Rumah Kebaya'      => 'DKI Jakarta',
            'Rumah Lamin'       => 'Kalimantan Timur',
            'Rumah Baileo'      => 'Maluku',
            'Rumah Musalaki'    => 'Nusa Tenggara Timur',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $rumahAdat,
            'Rumah adat %s berasal dari daerah...',
            'Daerah manakah yang memiliki rumah adat bernama %s?'
        ));

        // E. Pakaian adat & daerah asal
        $pakaianAdat = [
            'Ulos'          => 'Sumatera Utara',
            'Baju Bodo'     => 'Sulawesi Selatan',
            'Kebaya'        => 'Jawa',
            'Ulee Balang'   => 'Aceh',
            'Payas Agung'   => 'Bali',
            'Baju Cele'     => 'Maluku',
            'Baju Kurung'   => 'Riau',
            'Teluk Belanga' => 'Riau',
            'King Baba'     => 'Kalimantan Barat',
            'Aesan Gede'    => 'Sumatera Selatan',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $pakaianAdat,
            'Pakaian adat %s berasal dari daerah...',
            'Daerah manakah yang memiliki pakaian adat bernama %s?'
        ));

        // F. Istilah seni rupa & pengertian
        $istilahSeniRupa = [
            'Seni rupa 2 dimensi'  => 'karya seni yang hanya memiliki ukuran panjang dan lebar',
            'Seni rupa 3 dimensi'  => 'karya seni yang memiliki panjang, lebar, dan tinggi (memiliki volume)',
            'Seni rupa murni'      => 'karya seni yang dibuat semata-mata untuk keindahan',
            'Seni rupa terapan'    => 'karya seni yang dibuat dengan mempertimbangkan nilai kegunaan',
            'Tekstur'              => 'sifat permukaan suatu benda seni yang dapat dilihat atau diraba',
            'Komposisi'            => 'susunan unsur-unsur seni rupa sehingga menghasilkan karya yang serasi',
            'Proporsi'             => 'perbandingan ukuran antara satu bagian dengan bagian lain dalam sebuah karya',
            'Ragam hias'           => 'motif hias yang diulang-ulang dalam sebuah karya seni',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $istilahSeniRupa,
            'Apa yang dimaksud dengan istilah "%s" dalam seni rupa?',
            'Istilah dalam seni rupa yang berarti "%s" adalah...'
        ));

        // G. Jenis warna & pengertian
        $jenisWarna = [
            'Warna primer'        => 'warna dasar yang tidak dapat dihasilkan dari campuran warna lain',
            'Warna sekunder'      => 'warna hasil pencampuran dua warna primer',
            'Warna tersier'       => 'warna hasil pencampuran warna primer dengan warna sekunder terdekat',
            'Warna komplementer'  => 'warna yang letaknya saling berhadapan dalam lingkaran warna',
            'Warna analog'        => 'warna yang letaknya berdekatan dalam lingkaran warna',
            'Warna monokromatik'  => 'warna yang berasal dari satu warna dengan tingkat gelap terang berbeda',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $jenisWarna,
            'Apa yang dimaksud dengan "%s"?',
            'Jenis warna yang berarti "%s" disebut...'
        ));

        // H. Cabang seni & pengertian
        $cabangSeni = [
            'Seni musik'  => 'cabang seni yang menggunakan bunyi/suara sebagai media utamanya',
            'Seni tari'   => 'cabang seni yang menggunakan gerak tubuh sebagai media utamanya',
            'Seni rupa'   => 'cabang seni yang menggunakan unsur garis, bentuk, dan warna sebagai media utamanya',
            'Seni teater' => 'cabang seni pertunjukan yang menggabungkan gerak, dialog, dan akting di atas panggung',
            'Seni sastra' => 'cabang seni yang menggunakan bahasa tulis atau lisan sebagai media utamanya',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $cabangSeni,
            'Cabang seni "%s" adalah...',
            'Cabang seni yang merupakan "%s" disebut...'
        ));

        return $items;
    }

    // SEJARAH (pool > 150 soal berbasis data fakta, lalu diacak & diambil 150)

    private static function sejarahQuestions(): array
    {
        $items = [];

        // A. Tokoh proklamasi & perannya
        $tokohProklamasi = [
            'Soekarno'              => 'membacakan teks proklamasi kemerdekaan Indonesia',
            'Mohammad Hatta'        => 'mendampingi Soekarno menandatangani teks proklamasi',
            'Ahmad Soebardjo'       => 'menyusun teks proklamasi bersama Soekarno dan Hatta',
            'Sayuti Melik'          => 'mengetik naskah teks proklamasi',
            'Fatmawati'             => 'menjahit bendera pusaka Merah Putih',
            'Sukarni'               => 'mengusulkan agar teks proklamasi ditandatangani atas nama bangsa Indonesia',
            'Latief Hendraningrat'  => 'mengibarkan bendera pusaka pada saat proklamasi',
            'Chaerul Saleh'         => 'tokoh pemuda yang mendesak agar proklamasi segera dikumandangkan',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $tokohProklamasi,
            'Peran %s dalam peristiwa proklamasi kemerdekaan adalah...',
            'Tokoh yang berperan %s adalah...'
        ));

        // B. Pahlawan nasional & daerah asal perjuangan
        $pahlawanDaerah = [
            'Pangeran Diponegoro'    => 'Jawa Tengah',
            'Cut Nyak Dhien'         => 'Aceh',
            'Sultan Hasanuddin'      => 'Sulawesi Selatan',
            'Pattimura'              => 'Maluku',
            'Tuanku Imam Bonjol'     => 'Sumatera Barat',
            'Sisingamangaraja XII'   => 'Sumatera Utara',
            'I Gusti Ngurah Rai'     => 'Bali',
            'Untung Surapati'        => 'Jawa Timur',
            'Teuku Umar'             => 'Aceh',
            'Pangeran Antasari'      => 'Kalimantan Selatan',
            'Sultan Ageng Tirtayasa' => 'Banten',
            'Fatahillah'             => 'DKI Jakarta',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $pahlawanDaerah,
            'Pahlawan nasional %s berjuang di wilayah...',
            'Pahlawan nasional yang berjuang di wilayah %s adalah...'
        ));

        // C. Organisasi pergerakan nasional & tahun berdiri
        $organisasiTahun = [
            'Budi Utomo'                                  => '1908',
            'Sarekat Islam'                                => '1911',
            'Indische Partij'                              => '1912',
            'Muhammadiyah'                                 => '1912',
            'Jong Java'                                    => '1915',
            'Jong Sumatranen Bond'                         => '1917',
            'Taman Siswa'                                  => '1922',
            'Partai Nasional Indonesia'                    => '1927',
            'Nahdlatul Ulama'                               => '1926',
            'Indische Sociaal Democratische Vereeniging'   => '1914',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $organisasiTahun,
            'Organisasi pergerakan nasional %s didirikan pada tahun...',
            'Organisasi pergerakan nasional yang didirikan pada tahun %s adalah...'
        ));

        // D. Kerajaan di Indonesia & lokasinya
        $kerajaanLokasi = [
            'Kerajaan Sriwijaya'      => 'Sumatera Selatan',
            'Kerajaan Majapahit'      => 'Jawa Timur',
            'Kerajaan Tarumanegara'   => 'Jawa Barat',
            'Kerajaan Kutai'          => 'Kalimantan Timur',
            'Kerajaan Mataram Kuno'   => 'Jawa Tengah',
            'Kerajaan Singasari'      => 'Jawa Timur',
            'Kerajaan Demak'          => 'Jawa Tengah',
            'Kerajaan Gowa-Tallo'     => 'Sulawesi Selatan',
            'Kerajaan Ternate'        => 'Maluku Utara',
            'Kerajaan Banten'         => 'Banten',
            'Kerajaan Pajajaran'      => 'Jawa Barat',
            'Kerajaan Aceh Darussalam'=> 'Aceh',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $kerajaanLokasi,
            '%s berpusat di wilayah yang sekarang menjadi provinsi...',
            'Kerajaan yang berpusat di wilayah %s adalah...'
        ));

        // E. Peristiwa sejarah penting & tanggal terjadinya
        $peristiwaTanggal = [
            'Proklamasi kemerdekaan Indonesia'                 => '17 Agustus 1945',
            'Sumpah Pemuda'                                     => '28 Oktober 1928',
            'Berdirinya Budi Utomo (Hari Kebangkitan Nasional)' => '20 Mei 1908',
            'Pertempuran Surabaya'                              => '10 November 1945',
            'Peristiwa Rengasdengklok'                          => '16 Agustus 1945',
            'Konferensi Meja Bundar'                            => '23 Agustus 1949',
            'Perjanjian Renville'                               => '17 Januari 1948',
            'Perjanjian Linggarjati'                            => '25 Maret 1947',
            'Serangan Umum 1 Maret'                             => '1 Maret 1949',
            'Pengakuan kedaulatan Indonesia oleh Belanda'       => '27 Desember 1949',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $peristiwaTanggal,
            'Peristiwa "%s" terjadi pada tanggal...'
        ));

        // F. Peninggalan sejarah & lokasinya
        $peninggalanLokasi = [
            'Candi Borobudur'        => 'Jawa Tengah',
            'Candi Prambanan'        => 'Yogyakarta',
            'Candi Mendut'           => 'Jawa Tengah',
            'Candi Muara Takus'      => 'Riau',
            'Candi Penataran'        => 'Jawa Timur',
            'Candi Cangkuang'        => 'Jawa Barat',
            'Situs Trowulan'         => 'Jawa Timur',
            'Benteng Fort Rotterdam' => 'Sulawesi Selatan',
            'Masjid Agung Demak'     => 'Jawa Tengah',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $peninggalanLokasi,
            'Peninggalan sejarah %s berada di provinsi...',
            'Peninggalan sejarah yang berada di provinsi %s adalah...'
        ));

        // G. Istilah sejarah & pengertian
        $istilahSejarah = [
            'Prasasti'      => 'sumber sejarah tertulis yang dipahatkan pada batu atau logam',
            'Fosil'         => 'sisa-sisa makhluk hidup yang telah membatu dan menjadi bukti kehidupan masa lampau',
            'Artefak'       => 'benda peninggalan sejarah hasil buatan manusia pada masa lampau',
            'Kronologi'     => 'urutan waktu dari suatu peristiwa sejarah',
            'Historiografi' => 'penulisan sejarah berdasarkan sumber-sumber yang ada',
            'Folklore'      => 'cerita rakyat yang diwariskan secara turun-temurun',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $istilahSejarah,
            'Apa yang dimaksud dengan istilah "%s" dalam ilmu sejarah?',
            'Istilah sejarah yang berarti "%s" adalah...'
        ));

        // H. Zaman praaksara & cirinya
        $zamanPraaksara = [
            'Zaman Paleolitikum'  => 'zaman batu tua dengan alat-alat masih kasar dan belum diasah',
            'Zaman Mesolitikum'   => 'zaman batu tengah ketika manusia purba mulai hidup semi menetap',
            'Zaman Neolitikum'    => 'zaman batu muda dengan alat-alat sudah diasah halus dan manusia mulai bercocok tanam',
            'Zaman Megalitikum'   => 'zaman ketika manusia purba mulai membuat bangunan dari batu besar',
            'Zaman Logam'         => 'zaman ketika manusia purba mulai mengenal dan menggunakan alat dari logam',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $zamanPraaksara,
            'Ciri khas dari %s adalah...',
            'Zaman praaksara yang memiliki ciri "%s" disebut...'
        ));

        // I. Manusia purba & keterangannya
        $manusiaPurba = [
            'Meganthropus Paleojavanicus' => 'manusia purba tertua yang ditemukan di Sangiran, Jawa Tengah',
            'Pithecanthropus Erectus'     => 'manusia purba yang fosilnya ditemukan oleh Eugene Dubois di Trinil',
            'Homo Wajakensis'             => 'manusia purba yang ditemukan di daerah Wajak, Jawa Timur',
            'Homo Floresiensis'           => 'manusia purba berukuran kecil yang ditemukan di Pulau Flores',
            'Homo Soloensis'              => 'manusia purba yang ditemukan di sekitar aliran Sungai Bengawan Solo',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $manusiaPurba,
            'Keterangan yang tepat mengenai %s adalah...',
            'Jenis manusia purba yang sesuai dengan keterangan "%s" adalah...'
        ));

        // J. Hari nasional & tanggal peringatannya
        $hariNasional = [
            'Hari Pendidikan Nasional'    => '2 Mei',
            'Hari Kebangkitan Nasional'   => '20 Mei',
            'Hari Lahir Pancasila'        => '1 Juni',
            'Hari Sumpah Pemuda'          => '28 Oktober',
            'Hari Pahlawan'               => '10 November',
            'Hari Ibu'                    => '22 Desember',
            'Hari Guru Nasional'          => '25 November',
            'Hari Kesaktian Pancasila'    => '1 Oktober',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $hariNasional,
            '%s diperingati setiap tanggal...',
            'Hari nasional yang diperingati setiap tanggal %s adalah...'
        ));

        return $items;
    }

    // PENGETAHUAN SOSIAL / IPS (pool > 150 soal berbasis data fakta, lalu diacak & diambil 150)

    private static function pengetahuanSosialQuestions(): array
    {
        $items = [];

        // A. Provinsi & ibu kotanya
        $provinsiIbukota = [
            'Aceh'                       => 'Banda Aceh',
            'Sumatera Utara'             => 'Medan',
            'Sumatera Barat'             => 'Padang',
            'Riau'                       => 'Pekanbaru',
            'Kepulauan Riau'             => 'Tanjung Pinang',
            'Jambi'                      => 'Jambi',
            'Sumatera Selatan'           => 'Palembang',
            'Kepulauan Bangka Belitung'  => 'Pangkal Pinang',
            'Bengkulu'                   => 'Bengkulu',
            'Lampung'                    => 'Bandar Lampung',
            'DKI Jakarta'                => 'Jakarta',
            'Jawa Barat'                 => 'Bandung',
            'Jawa Tengah'                => 'Semarang',
            'DI Yogyakarta'              => 'Yogyakarta',
            'Jawa Timur'                 => 'Surabaya',
            'Banten'                     => 'Serang',
            'Bali'                       => 'Denpasar',
            'Nusa Tenggara Barat'        => 'Mataram',
            'Nusa Tenggara Timur'        => 'Kupang',
            'Kalimantan Barat'           => 'Pontianak',
            'Kalimantan Tengah'          => 'Palangka Raya',
            'Kalimantan Selatan'         => 'Banjarmasin',
            'Kalimantan Timur'           => 'Samarinda',
            'Kalimantan Utara'           => 'Tanjung Selor',
            'Sulawesi Utara'             => 'Manado',
            'Sulawesi Tengah'            => 'Palu',
            'Sulawesi Selatan'           => 'Makassar',
            'Sulawesi Tenggara'          => 'Kendari',
            'Gorontalo'                  => 'Gorontalo',
            'Sulawesi Barat'             => 'Mamuju',
            'Maluku'                     => 'Ambon',
            'Maluku Utara'               => 'Sofifi',
            'Papua'                      => 'Jayapura',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $provinsiIbukota,
            'Ibu kota provinsi %s adalah...',
            'Kota yang menjadi ibu kota dari provinsi dengan ibu kota %s termasuk dalam provinsi...',
            3
        ));

        // B. Kenampakan alam & pengertiannya
        $kenampakanAlam = [
            'Dataran tinggi' => 'wilayah daratan yang memiliki ketinggian lebih dari 700 meter di atas permukaan laut',
            'Dataran rendah' => 'wilayah daratan yang memiliki ketinggian kurang dari 200 meter di atas permukaan laut',
            'Pegunungan'     => 'rangkaian gunung yang saling bersambungan dan memanjang',
            'Delta'          => 'daratan yang terbentuk dari endapan lumpur di muara sungai',
            'Selat'          => 'laut sempit yang menghubungkan dua pulau',
            'Teluk'          => 'bagian laut yang menjorok ke daratan',
            'Tanjung'        => 'bagian daratan yang menjorok ke laut',
            'Danau'          => 'genangan air yang luas dan dikelilingi oleh daratan',
            'Palung'         => 'bagian dasar laut yang sangat dalam dan curam',
            'Semenanjung'    => 'daratan yang menjorok ke laut dan dikelilingi air pada sebagian besar sisinya',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $kenampakanAlam,
            'Apa yang dimaksud dengan "%s"?',
            'Kenampakan alam yang berarti "%s" disebut...'
        ));

        // C. Kegiatan ekonomi & pengertiannya
        $kegiatanEkonomi = [
            'Produksi'    => 'kegiatan menghasilkan atau menambah nilai guna suatu barang/jasa',
            'Distribusi'  => 'kegiatan menyalurkan barang/jasa dari produsen ke konsumen',
            'Konsumsi'    => 'kegiatan menggunakan atau menghabiskan nilai guna suatu barang/jasa',
            'Produsen'    => 'pihak yang melakukan kegiatan produksi',
            'Distributor' => 'pihak yang melakukan kegiatan penyaluran barang',
            'Konsumen'    => 'pihak yang menggunakan barang atau jasa',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $kegiatanEkonomi,
            'Istilah "%s" dalam kegiatan ekonomi berarti...',
            'Istilah ekonomi yang berarti "%s" adalah...'
        ));

        // D. Norma dalam masyarakat & pengertiannya
        $normaMasyarakat = [
            'Norma agama'      => 'aturan hidup yang bersumber dari ajaran suatu agama',
            'Norma kesusilaan' => 'aturan hidup yang bersumber dari hati nurani manusia',
            'Norma kesopanan'  => 'aturan hidup yang bersumber dari pergaulan masyarakat',
            'Norma hukum'      => 'aturan hidup yang dibuat oleh lembaga negara dan bersifat memaksa',
            'Norma adat'       => 'aturan hidup yang bersumber dari kebiasaan turun-temurun suatu masyarakat',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $normaMasyarakat,
            '%s adalah aturan hidup yang...',
            'Norma yang merupakan aturan hidup "%s" disebut...'
        ));

        // E. Lembaga sosial & fungsinya
        $lembagaSosial = [
            'Keluarga'         => 'menjadi tempat pertama sosialisasi dan pendidikan bagi anak',
            'Sekolah'          => 'menjadi tempat berlangsungnya pendidikan formal',
            'Lembaga ekonomi'  => 'mengatur kegiatan produksi, distribusi, dan konsumsi dalam masyarakat',
            'Lembaga agama'    => 'mengatur kehidupan spiritual dan keagamaan masyarakat',
            'Lembaga hukum'    => 'menegakkan aturan dan keadilan dalam masyarakat',
            'Lembaga politik'  => 'mengatur pemerintahan dan kekuasaan dalam suatu negara',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $lembagaSosial,
            'Fungsi utama dari %s dalam masyarakat adalah...',
            'Lembaga sosial yang berfungsi untuk %s adalah...'
        ));

        // F. Negara ASEAN & ibu kotanya
        $negaraAseanIbukota = [
            'Indonesia'          => 'Jakarta',
            'Malaysia'           => 'Kuala Lumpur',
            'Singapura'          => 'Singapura',
            'Thailand'           => 'Bangkok',
            'Filipina'           => 'Manila',
            'Vietnam'            => 'Hanoi',
            'Myanmar'            => 'Naypyidaw',
            'Kamboja'            => 'Phnom Penh',
            'Laos'               => 'Vientiane',
            'Brunei Darussalam'  => 'Bandar Seri Begawan',
            'Timor Leste'        => 'Dili',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $negaraAseanIbukota,
            'Ibu kota negara %s adalah...',
            'Negara yang memiliki ibu kota %s adalah...'
        ));

        // G. Negara ASEAN & mata uangnya
        $negaraAseanMataUang = [
            'Indonesia'          => 'Rupiah',
            'Malaysia'           => 'Ringgit',
            'Singapura'          => 'Dolar Singapura',
            'Thailand'           => 'Baht',
            'Filipina'           => 'Peso',
            'Vietnam'            => 'Dong',
            'Myanmar'            => 'Kyat',
            'Kamboja'            => 'Riel',
            'Laos'               => 'Kip',
            'Brunei Darussalam'  => 'Dolar Brunei',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $negaraAseanMataUang,
            'Mata uang yang digunakan di negara %s adalah...',
            'Negara yang menggunakan mata uang %s adalah...'
        ));

        // H. Sumber daya alam & pemanfaatannya
        $sumberDayaAlam = [
            'Minyak bumi' => 'diolah menjadi bahan bakar seperti bensin dan solar',
            'Batu bara'   => 'digunakan sebagai bahan bakar pembangkit listrik tenaga uap',
            'Gas alam'    => 'digunakan sebagai bahan bakar rumah tangga dan industri',
            'Emas'        => 'digunakan sebagai bahan perhiasan dan cadangan devisa negara',
            'Nikel'       => 'digunakan sebagai bahan baku baterai dan pelapis logam anti karat',
            'Timah'       => 'digunakan sebagai bahan baku pelapis kaleng dan solder',
            'Hutan'       => 'menghasilkan kayu serta menjaga keseimbangan lingkungan',
            'Tanah'       => 'digunakan untuk lahan pertanian dan tempat tinggal',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $sumberDayaAlam,
            'Sumber daya alam %s dimanfaatkan untuk...',
            'Sumber daya alam yang dimanfaatkan untuk %s adalah...'
        ));

        return $items;
    }
}