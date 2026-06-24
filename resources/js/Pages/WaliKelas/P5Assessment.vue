<template>
  <Head title="Asesmen P5" />

  <AppLayout
    title="Asesmen P5 (Profil Pelajar Pancasila)"
    subtitle="Input capaian dimensi Profil Pelajar Pancasila untuk kelas perwalian Anda"
    :navigation="navigation"
  >
    <template #topbar-actions>
      <button class="px-4 py-2 bg-green-600 hover:bg-green-500 rounded-lg text-sm font-semibold text-white transition-colors shadow-lg shadow-green-500/30 flex items-center gap-2">
        <span>💾</span> Simpan Asesmen
      </button>
    </template>

    <div class="space-y-6">

      <!-- Pilihan Tema & Dimensi -->
      <div class="flex gap-4">
        <div class="flex-1 rounded-2xl border border-white/8 p-5" style="background: var(--card)">
          <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Tema Projek (Fase F)</label>
          <select v-model="selectedTema" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white outline-none focus:border-green-500">
            <option value="1">Gaya Hidup Berkelanjutan: "Sekolahku Hijau, Masa Depanku Cerah"</option>
            <option value="2">Kearifan Lokal: "Mengenal Budaya Betawi"</option>
          </select>
        </div>
        
        <div class="flex-1 rounded-2xl border border-white/8 p-5" style="background: var(--card)">
          <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Dimensi Penilaian</label>
          <select v-model="selectedDimensi" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white outline-none focus:border-green-500">
            <option value="1">Beriman, Bertakwa kepada Tuhan YME, dan Berakhlak Mulia</option>
            <option value="2">Bergotong Royong</option>
            <option value="3">Bernalar Kritis</option>
            <option value="4">Kreatif</option>
          </select>
        </div>
      </div>

      <!-- Info Rubrik -->
      <div class="rounded-xl border border-indigo-500/30 bg-indigo-500/10 p-4 flex gap-6">
        <div class="flex-1">
          <div class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest mb-1">BB (Belum Berkembang)</div>
          <div class="text-xs text-slate-300">Siswa masih membutuhkan bimbingan intensif.</div>
        </div>
        <div class="flex-1">
          <div class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest mb-1">MB (Mulai Berkembang)</div>
          <div class="text-xs text-slate-300">Siswa mulai menunjukkan pemahaman namun belum konsisten.</div>
        </div>
        <div class="flex-1">
          <div class="text-[10px] font-bold text-green-400 uppercase tracking-widest mb-1">BSH (Berkembang Sesuai Harapan)</div>
          <div class="text-xs text-slate-300">Siswa telah menunjukkan pencapaian sesuai target.</div>
        </div>
        <div class="flex-1">
          <div class="text-[10px] font-bold text-purple-400 uppercase tracking-widest mb-1">SB (Sangat Berkembang)</div>
          <div class="text-xs text-slate-300">Siswa melampaui harapan dan menjadi teladan.</div>
        </div>
      </div>

      <!-- Tabel Input Nilai -->
      <div class="rounded-2xl border border-white/8 overflow-hidden" style="background: var(--card)">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-white/4 border-b border-white/8">
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider w-12 text-center border-r border-white/8">No</th>
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-r border-white/8">Nama Siswa</th>
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-center w-24 border-r border-white/8">BB</th>
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-center w-24 border-r border-white/8">MB</th>
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-center w-24 border-r border-white/8">BSH</th>
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-center w-24 border-r border-white/8">SB</th>
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Catatan Khusus <span class="normal-case font-normal text-slate-500">(opsional)</span></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(siswa, idx) in students" :key="siswa.id" class="border-b border-white/4 hover:bg-white/2 transition-colors">
                <td class="px-4 py-3 text-sm text-slate-500 text-center border-r border-white/8">{{ idx + 1 }}</td>
                <td class="px-4 py-3 border-r border-white/8">
                  <div class="text-sm font-semibold text-white">{{ siswa.nama }}</div>
                </td>
                
                <!-- Radio Buttons Custom -->
                <td class="border-r border-white/8 text-center bg-white/2 p-0 relative hover:bg-white/5 transition-colors cursor-pointer" @click="siswa.nilai = 'BB'">
                  <input type="radio" :name="`nilai-${siswa.id}`" value="BB" v-model="siswa.nilai" class="sr-only peer" />
                  <div class="absolute inset-0 border-2 border-transparent peer-checked:border-indigo-500 transition-all z-10 pointer-events-none"></div>
                  <div class="w-full h-12 flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-opacity">
                    <div class="w-4 h-4 rounded-full bg-indigo-500"></div>
                  </div>
                </td>
                
                <td class="border-r border-white/8 text-center bg-white/2 p-0 relative hover:bg-white/5 transition-colors cursor-pointer" @click="siswa.nilai = 'MB'">
                  <input type="radio" :name="`nilai-${siswa.id}`" value="MB" v-model="siswa.nilai" class="sr-only peer" />
                  <div class="absolute inset-0 border-2 border-transparent peer-checked:border-indigo-500 transition-all z-10 pointer-events-none"></div>
                  <div class="w-full h-12 flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-opacity">
                    <div class="w-4 h-4 rounded-full bg-indigo-500"></div>
                  </div>
                </td>

                <td class="border-r border-white/8 text-center bg-white/2 p-0 relative hover:bg-white/5 transition-colors cursor-pointer" @click="siswa.nilai = 'BSH'">
                  <input type="radio" :name="`nilai-${siswa.id}`" value="BSH" v-model="siswa.nilai" class="sr-only peer" />
                  <div class="absolute inset-0 border-2 border-transparent peer-checked:border-green-500 transition-all z-10 pointer-events-none"></div>
                  <div class="w-full h-12 flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-opacity">
                    <div class="w-4 h-4 rounded-full bg-green-500 shadow-[0_0_10px_rgba(34,197,94,0.5)]"></div>
                  </div>
                </td>

                <td class="border-r border-white/8 text-center bg-white/2 p-0 relative hover:bg-white/5 transition-colors cursor-pointer" @click="siswa.nilai = 'SB'">
                  <input type="radio" :name="`nilai-${siswa.id}`" value="SB" v-model="siswa.nilai" class="sr-only peer" />
                  <div class="absolute inset-0 border-2 border-transparent peer-checked:border-purple-500 transition-all z-10 pointer-events-none"></div>
                  <div class="w-full h-12 flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-opacity">
                    <div class="w-4 h-4 rounded-full bg-purple-500 shadow-[0_0_10px_rgba(168,85,247,0.5)]"></div>
                  </div>
                </td>

                <td class="p-2">
                  <input type="text" v-model="siswa.catatan" placeholder="Tulis catatan perkembangan..." 
                         class="w-full bg-black/20 border border-transparent rounded-lg px-3 py-1.5 text-xs text-slate-300 focus:border-white/20 focus:bg-white/10 outline-none transition-colors" />
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

const selectedTema = ref("1");
const selectedDimensi = ref("2");

const students = ref([
  { id: 1, nama: 'Ahmad Rifai', nilai: 'BSH', catatan: 'Aktif berkolaborasi dalam kelompok.' },
  { id: 2, nama: 'Budi Santoso', nilai: 'SB', catatan: 'Mengambil peran pemimpin diskusi secara inisiatif.' },
  { id: 3, nama: 'Citra Dewi', nilai: 'MB', catatan: 'Masih malu-malu mengungkapkan pendapat.' },
  { id: 4, nama: 'Dian Pratama', nilai: 'BB', catatan: 'Cenderung pasif dan jarang hadir saat proyek.' },
  { id: 5, nama: 'Eka Rahayu', nilai: 'BSH', catatan: '' },
]);
</script>
