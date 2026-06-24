<template>
  <Head title="Dashboard Wali Kelas" />

  <AppLayout
    title="Dashboard Wali Kelas"
    subtitle="Pantau kehadiran, nilai rapor, dan performa kelas perwalian Anda"
    :navigation="navigation"
  >
    <div class="space-y-6 max-w-6xl">
      
      <!-- Ringkasan Kelas -->
      <div class="grid grid-cols-4 gap-4">
        <div class="col-span-1 rounded-2xl border border-white/8 p-5 bg-gradient-to-br from-indigo-500/20 to-purple-500/10 shadow-lg">
          <div class="text-[10px] font-bold text-indigo-300 uppercase tracking-widest mb-1">Kelas Perwalian</div>
          <div class="text-3xl font-black text-white">XII TKJ 1</div>
          <div class="text-xs text-indigo-200 mt-2 flex items-center gap-2">
            <span>👥 36 Siswa</span>
            <span>•</span>
            <span>T.A 2026 Ganjil</span>
          </div>
        </div>

        <div class="col-span-1 rounded-2xl border border-white/8 p-5 bg-white/2">
          <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Kehadiran Hari Ini</div>
          <div class="flex items-end gap-2">
            <div class="text-3xl font-black text-green-400">98%</div>
          </div>
          <div class="text-xs text-slate-400 mt-2">1 Alpa (Dian Pratama)</div>
        </div>

        <div class="col-span-1 rounded-2xl border border-white/8 p-5 bg-white/2">
          <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Status Rapor Semester</div>
          <div class="flex items-end gap-2">
            <div class="text-3xl font-black text-yellow-400">45%</div>
          </div>
          <div class="text-xs text-slate-400 mt-2">Guru mapel menginput nilai</div>
        </div>

        <div class="col-span-1 rounded-2xl border border-white/8 p-5 bg-white/2">
          <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Skor Wali Kelas (Siswa)</div>
          <div class="flex items-end gap-2">
            <div class="text-3xl font-black text-white">4.8</div>
            <div class="text-sm text-slate-500 font-bold mb-1">/ 5.0</div>
          </div>
          <div class="text-xs text-slate-400 mt-2">Berdasarkan 30 responden</div>
        </div>
      </div>

      <!-- Action & P5 Banner -->
      <div class="rounded-2xl border border-green-500/30 p-1 bg-gradient-to-r from-green-500/10 to-transparent flex items-center">
        <div class="p-5 flex-1">
          <h3 class="font-bold text-white text-lg flex items-center gap-2">
            <span>🌱</span> Asesmen P5 (Profil Pelajar Pancasila)
          </h3>
          <p class="text-sm text-slate-400 mt-1">Sebagai wali kelas, Anda wajib mengisi deskripsi dan capaian P5 siswa sebelum cetak rapor akhir.</p>
        </div>
        <div class="pr-5">
          <a href="/walikelas/p5-assessment" class="px-5 py-3 bg-green-600 hover:bg-green-500 text-white rounded-xl text-sm font-bold transition-all shadow-lg shadow-green-500/30 flex items-center gap-2">
            Mulai Input P5 <span>→</span>
          </a>
        </div>
      </div>

      <!-- Daftar Siswa Perwalian -->
      <div class="rounded-2xl border border-white/8 overflow-hidden" style="background: var(--card)">
        <div class="px-5 py-4 border-b border-white/8 flex items-center justify-between bg-white/4">
          <h3 class="font-bold text-sm">Daftar Siswa & Progres Nilai</h3>
          <input type="text" placeholder="Cari nama siswa..." class="bg-black/20 border border-white/10 rounded-lg px-3 py-1.5 text-xs text-white outline-none focus:border-indigo-500 w-64" />
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-white/2 border-b border-white/8">
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider w-12 text-center">No</th>
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nama Siswa / NIS</th>
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-center">Kehadiran Sem.</th>
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-center">Poin Pelanggaran</th>
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider w-64">Kelengkapan Rapor</th>
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(siswa, idx) in students" :key="siswa.id" class="border-b border-white/4 hover:bg-white/2 transition-colors">
                <td class="px-4 py-3 text-sm text-slate-500 text-center">{{ idx + 1 }}</td>
                <td class="px-4 py-3">
                  <div class="text-sm font-semibold text-white">{{ siswa.nama }}</div>
                  <div class="text-[10px] text-slate-500 mt-0.5">{{ siswa.nis }}</div>
                </td>
                <td class="px-4 py-3 text-center">
                  <div class="text-sm font-bold" :class="siswa.kehadiran < 90 ? 'text-yellow-400' : 'text-green-400'">{{ siswa.kehadiran }}%</div>
                </td>
                <td class="px-4 py-3 text-center">
                  <div class="inline-block px-2 py-0.5 rounded text-xs font-bold" :class="siswa.poin > 0 ? 'bg-red-500/20 text-red-400' : 'text-slate-500'">
                    {{ siswa.poin }}
                  </div>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <div class="flex-1 h-2 bg-white/10 rounded-full overflow-hidden">
                      <div class="h-full bg-indigo-500 rounded-full" :style="`width: ${siswa.raporPct}%`"></div>
                    </div>
                    <span class="text-[10px] font-bold text-slate-400">{{ siswa.raporPct }}%</span>
                  </div>
                </td>
                <td class="px-4 py-3 text-center">
                  <button class="text-xs font-bold text-indigo-400 hover:text-indigo-300 transition-colors">Lihat Detail</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const navigation = [
  {
    label: 'Perwalian',
    items: [
      { href: '/walikelas/dashboard', icon: '🏠', label: 'Dashboard Kelas' },
      { href: '/walikelas/jurnal', icon: '📓', label: 'Jurnal KBM Kelas' },
      { href: '/walikelas/p5-assessment', icon: '🌱', label: 'Asesmen P5' },
    ],
  },
];

const students = ref([
  { id: 1, nama: 'Ahmad Rifai', nis: '2024001', kehadiran: 98, poin: 0, raporPct: 45 },
  { id: 2, nama: 'Budi Santoso', nis: '2024002', kehadiran: 100, poin: 0, raporPct: 45 },
  { id: 3, nama: 'Citra Dewi', nis: '2024003', kehadiran: 95, poin: 10, raporPct: 45 },
  { id: 4, nama: 'Dian Pratama', nis: '2024004', kehadiran: 85, poin: 50, raporPct: 30 },
  { id: 5, nama: 'Eka Rahayu', nis: '2024005', kehadiran: 92, poin: 0, raporPct: 50 },
]);
</script>
