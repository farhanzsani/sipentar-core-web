<?php

namespace App\Enums;

enum UserRole: string
{
    case PEMILIK = 'pemilik';
    case PEGAWAI = 'pegawai';
}
