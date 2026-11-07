@extends('layouts.admin')
@section('title', 'Mata Pelajaran')
@section('styles')<link rel="stylesheet" href="{{ asset('admin css/matapelajaran.css') }}?v=7">@endsection
@section('content')

<div class="admin-page-header">
  <div>
    <h1>Mata Pelajaran</h1>
    <p>Daftar mata pelajaran dan guru pengampu dari Data Guru</p>
  </div>
  <div class="admin-header-actions mapel-header-actions">
    <button class="admin-action admin-pdf-export bg-blue-600 hover:bg-blue-700" id="exportMapel" type="button" data-pdf-export data-export-url="{{ route('admin.guru.export-pdf') }}" data-export-filename="kurikulum-mapel.pdf" aria-live="polite">
      <svg class="admin-pdf-export-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 2.5v9m0 0 3.5-3.5M10 11.5 6.5 8M3.5 12.5v3A1.5 1.5 0 0 0 5 17h10a1.5 1.5 0 0 0 1.5-1.5v-3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <span data-export-label>Unduh PDF</span><span class="admin-pdf-progress" aria-hidden="true"></span>
    </button>
  </div>
</div>

@php
  // Helper Generator Singkatan Otomatis untuk Mapel Baru maupun Eksisting
  $makeAbbreviation = function($namaMapel, $kodeDefault = '') {
      $namaClean = trim(preg_replace('/[^\w\s]/u', '', $namaMapel));
      $namaLower = strtolower($namaClean);

      // Kamus Singkatan Standar Mapel Umum
      $dictionary = [
          'bahasa inggris' => 'B.ING',
          'bahasa indonesia' => 'B.IND',
          'bahasa arab' => 'B.ARB',
          'bahasa sunda' => 'B.SND',
          'matematika' => 'MTK',
          'ilmu pengetahuan alam' => 'IPA',
          'ipa terpadu' => 'IPA',
          'ilmu pengetahuan sosial' => 'IPS',
          'ips terpadu' => 'IPS',
          'pendidikan pancasila' => 'PKN',
          'pendidikan kewarganegaraan' => 'PKN',
          'pendidikan agama islam' => 'PAI',
          'seni budaya' => 'SBD',
          'informatika' => 'INF',
          'penjaskes' => 'PJOK',
          'pendidikan jasmani olahraga dan kesehatan' => 'PJOK',
          'prakarya' => 'PKR',
      ];

      if (array_key_exists($namaLower, $dictionary)) {
          return $dictionary[$namaLower];
      }

      // Jika ada kata tunggal yang pendek (< 4 huruf), pakai langsung
      $words = array_values(array_filter(explode(' ', $namaClean)));
      if (count($words) === 1 && mb_strlen($words[0]) <= 4) {
          return strtoupper($words[0]);
      }

      // Fallback Otomatis untuk Mapel Baru: Ambil huruf kapital/pertama tiap kata
      $abbr = '';
      foreach ($words as $word) {
          if (!in_array(strtolower($word), ['dan', 'atau', 'dengan', 'yang', 'untuk'])) {
              $abbr .= mb_substr($word, 0, 1);
          }
      }

      return mb_strlen($abbr) >= 2 ? strtoupper(mb_substr($abbr, 0, 4)) : strtoupper(mb_substr($namaClean, 0, 3));
  };

  // Mapping Data Mapel untuk Modal Detail
  $mapelDetails = $mapels->mapWithKeys(function ($mapel) use ($makeAbbreviation) {
    $shortCode = $makeAbbreviation($mapel->nama_mapel, $mapel->kode);
    return [
      $mapel->id => [
        'nama' => $mapel->nama_mapel,
        'kode' => $shortCode,
        'gurus' => $mapel->gurus->map(fn ($guru) => [
          'nama' => $guru->nama_lengkap,
          'identitas' => $guru->nbm ?: ($guru->nip ?? null),
          'jp' => (int) $guru->pivot->jumlah_jam,
        ])->values(),
      ],
    ];
  });
@endphp

<div class="mapel-summary">
  <span class="admin-total" id="mapelTotal">Total {{ $mapels->count() }} Mapel</span>
</div>

<div class="admin-grid mapel-grid">
  @forelse ($mapels as $mapel)
    @php
      $shortCode = $makeAbbreviation($mapel->nama_mapel, $mapel->kode);
      $totalGuru = $mapel->gurus->count();
      $totalJam = $mapel->gurus->sum(fn ($guru) => (int) $guru->pivot->jumlah_jam);
    @endphp
    <article class="subject-card">
      <div class="subject-top">
        <span class="subject-code">{{ $shortCode }}</span>
      </div>
      <h3>{{ $mapel->nama_mapel }}</h3>
      <div class="subject-meta">
        <span>👨‍🏫 {{ $totalGuru }} Guru</span>
        <span>⏱️ {{ $totalJam }} JP/Minggu</span>
      </div>
      <div class="subject-footer">
        <button class="link-action mapel-manage-btn" type="button" data-mapel-id="{{ $mapel->id }}">
          <span>Detail &amp; Pengampu</span>
        </button>
      </div>
    </article>
  @empty
    <div class="mapel-empty-state">
      Belum ada mata pelajaran. Data akan muncul otomatis setelah admin memasukkan penugasan di Data Guru.
    </div>
  @endforelse
</div>

<!-- Modal Detail & Daftar Guru Pengampu -->
<dialog class="admin-modal mapel-modal" id="detailMapelModal">
  <div class="admin-modal-content">
    <div class="admin-modal-header">
      <h2 id="modalMapelTitle">Detail Mata Pelajaran</h2>
      <button class="admin-modal-close" type="button" onclick="closeDetailMapelModal()">&times;</button>
    </div>
    <div class="admin-modal-body">
      <div class="mapel-detail-header">
        <div class="mapel-detail-badge" id="modalMapelCode"></div>
        <div>
          <h3 id="modalMapelNama"></h3>
        </div>
      </div>

      <div class="mapel-detail-stats">
        <div class="stat-box">
          <span class="stat-label">Total Guru Pengampu</span>
          <strong class="stat-val" id="modalMapelGuruCount"></strong>
        </div>
        <div class="stat-box">
          <span class="stat-label">Total Beban Mengajar</span>
          <strong class="stat-val" id="modalMapelJP"></strong>
        </div>
      </div>

      <h4 class="mapel-section-title">👨‍🏫 Daftar Guru Pengampu</h4>
      <div class="guru-pengampu-list" id="modalGuruList">
        <!-- Rendered via JS -->
      </div>
    </div>
    <div class="admin-modal-footer">
      <button class="admin-action admin-action-outline" type="button" onclick="closeDetailMapelModal()">Tutup</button>
    </div>
  </div>
</dialog>

<script>
  const detailModal = document.getElementById('detailMapelModal');
  const mapelDetails = @json($mapelDetails);

  function openDetailMapelModal(data) {
    document.getElementById('modalMapelTitle').textContent = `Detail ${data.nama}`;
    document.getElementById('modalMapelCode').textContent = data.kode;
    document.getElementById('modalMapelNama').textContent = data.nama;
    document.getElementById('modalMapelGuruCount').textContent = `${data.gurus.length} Guru`;
    document.getElementById('modalMapelJP').textContent = `${data.gurus.reduce((total, guru) => total + guru.jp, 0)} JP/Minggu`;

    const guruListContainer = document.getElementById('modalGuruList');
    guruListContainer.replaceChildren();

    if (data.gurus.length > 0) {
      data.gurus.forEach(g => {
        const item = document.createElement('div');
        item.className = 'guru-pengampu-item';
        
        const info = document.createElement('div');
        info.className = 'guru-info';
        
        const name = document.createElement('strong');
        name.textContent = g.nama;
        
        const identity = document.createElement('span');
        identity.textContent = `NBM/NIP: ${g.identitas || 'Belum diisi'}`;
        
        info.append(name, identity);

        const hours = document.createElement('span');
        hours.className = 'guru-hours';
        hours.textContent = `${g.jp} JP/Minggu`;

        item.append(info, hours);
        guruListContainer.appendChild(item);
      });
    } else {
      const emptyState = document.createElement('p');
      emptyState.className = 'text-muted text-center';
      emptyState.textContent = 'Belum ada guru yang ditugaskan di mapel ini.';
      guruListContainer.appendChild(emptyState);
    }

    detailModal.showModal();
  }

  function closeDetailMapelModal() { detailModal.close(); }

  document.querySelectorAll('[data-mapel-id]').forEach(button => {
    button.addEventListener('click', () => {
      const mapelId = button.dataset.mapelId;
      if (mapelDetails[mapelId]) {
        openDetailMapelModal(mapelDetails[mapelId]);
      }
    });
  });
</script>
<script src="{{ asset('admin-pdf-export.js') }}?v=1" defer></script>
@endsection