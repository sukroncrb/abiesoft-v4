<?php

declare(strict_types=1);

namespace Abiesoft\App\Shared\Helpers;

use DateTime;
use DateTimeZone;
use IntlDateFormatter;

trait Tanggal
{

    public function hariDanTanggal($datetime) {
        $locale = 'id_ID';
        $dateFormatter = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::FULL,
            IntlDateFormatter::NONE,
            'Asia/Jakarta'
        );
        $tanggal = new DateTime($datetime);
        return $dateFormatter->format($tanggal);  // Contoh Outputnya : Sabtu, 20 September 2025
    }

    public function tanggalFull($datetime) {
        $locale = 'id_ID';
        $dateFormatter = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::LONG,
            IntlDateFormatter::NONE,
            'Asia/Jakarta'
        );
        $tanggal = new DateTime($datetime);
        return $dateFormatter->format($tanggal); // Contoh Outputnya : 20 September 2025
    }

    public function tanggalSimpel($datetime) {
        $locale = 'id_ID';
        $dateFormatter = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::NONE,
            'Asia/Jakarta'
        );
        $tanggal = new DateTime($datetime);
        return $dateFormatter->format($tanggal); // Contoh Outputnya : 20 Sep 2025
    }

    public function tanggalSlash($datetime) {
        $locale = 'id_ID';
        $dateFormatter = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::SHORT,
            IntlDateFormatter::NONE,
            'Asia/Jakarta'
        );
        $tanggal = new DateTime($datetime);
        return $dateFormatter->format($tanggal); // Contoh Outputnya : 20/09/25
    }

    public function facebook($datetime) {
        $tz = new DateTimeZone('Asia/Jakarta');
        $waktu_sekarang = new DateTime('now', $tz);
        $waktu_post = new DateTime($datetime, $tz);
        $selisih = $waktu_sekarang->diff($waktu_post);
        if ($selisih->y > 0) {
            return $waktu_post->format('j M Y \p\a\d\a H:i');
        } elseif ($selisih->m > 0) {
            return $waktu_post->format('j M Y \p\a\d\a H:i');
        } elseif ($selisih->d >= 7) {
            return $waktu_post->format('j M \p\a\d\a H:i');
        } elseif ($selisih->d > 1) {
            return $selisih->d . ' hari yang lalu';
        } elseif ($selisih->d == 1) {
            return 'Kemarin pada ' . $waktu_post->format('H:i');
        } elseif ($selisih->h > 0) {
            return $selisih->h . ' jam yang lalu';
        } elseif ($selisih->i > 0) {
            return $selisih->i . ' menit yang lalu';
        } else {
            return 'Baru saja';
        }
    }

}