<template>
  <Head title="Tugas Piket" />

  <AppLayout
    title="Laporan Kehadiran Guru"
    :subtitle="`Shift ${shift} — ${hariIni}`"
    :navigation="navigation"
  >
    <template #topbar-actions>
      <button @click="generateLaporanWa" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-green-500 hover:bg-green-400 text-white text-sm font-bold transition-all shadow-[0_0_15px_rgba(34,197,94,0.3)] hover:scale-105">
        <span class="text-lg">💬</span> Share Laporan ke WA
      </button>
    </template>

    <div class="bg-[#18181B] border border-white/10 rounded-2xl overflow-hidden shadow-2xl">
      <div class="p-5 border-b border-white/10 flex items-center justify-between">
        <div>
          <h3 class="text-lg font-black text-white">Daftar Guru Terjadwal Shift {{ shift }}</h3>
          <p class="text-xs text-slate-400">Silakan ceklis atau pilih status kehadiran guru secara real-time.</p>
        </div>
        <div class="flex items-center gap-3">
           <div class="px-3 py-1 bg-white/5 border border-white/10 rounded-lg text-xs font-semibold text-slate-300">
             Total: {{ teachers.length }} Guru
           </div>
        </div>
      </div>
      
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-white/5 border-b border-white/10">
              <th class="px-6 py-4 text-xs font-semibold text-slate-300 uppercase tracking-wider w-10">No</th>
              <th class="px-6 py-4 text-xs font-semibold text-slate-300 uppercase tracking-wider">Hadir?</th>
              <th class="px-6 py-4 text-xs font-semibold text-slate-300 uppercase tracking-wider">Nama Guru</th>
              <th class="px-6 py-4 text-xs font-semibold text-slate-300 uppercase tracking-wider">Jadwal Pertama</th>
              <th class="px-6 py-4 text-xs font-semibold text-slate-300 uppercase tracking-wider">Status & Ket.</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/10">
            <tr v-for="(guru, index) in teachers" :key="guru.id_guru" 
                class="transition-colors"
                :class="guru.status === 'HADIR' ? 'bg-green-900/10' : 'hover:bg-white/5'">
              <td class="px-6 py-4 text-slate-400 text-sm font-mono">{{ index + 1 }}</td>
              
              <!-- Ceklis Cepat Hadir -->
              <td class="px-6 py-4">
                <button 
                  @click="toggleHadir(guru)"
                  class="w-8 h-8 rounded-lg flex items-center justify-center transition-all border shadow-sm"
                  :class="guru.status === 'HADIR' ? 'bg-green-500 border-green-400 text-white' : 'bg-white/5 border-white/20 text-transparent hover:bg-white/10'"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                </button>
              </td>
              
              <td class="px-6 py-4">
                <div class="font-bold text-white">{{ guru.nama_guru }}</div>
              </td>
              
              <td class="px-6 py-4">
                <div class="text-sm font-semibold text-indigo-400">Jam ke-{{ guru.jam_ke }}</div>
              </td>
              
              <!-- Opsi Status Lainnya -->
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <select 
                    v-model="guru.status" 
                    @change="updateStatus(guru)"
                    class="bg-white/5 border border-white/10 text-white rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:border-indigo-500 min-w-[100px]">
                    <option :value="null">-- Belum Ada --</option>
                    <option value="HADIR">HADIR</option>
                    <option value="SAKIT">SAKIT</option>
                    <option value="IZIN">IZIN</option>
                    <option value="LAINNYA">Manual / Lainnya</option>
                  </select>

                  <input 
                    v-if="guru.status !== 'HADIR'"
                    v-model="guru.keterangan_jam"
                    @blur="updateStatus(guru)"
                    placeholder="Contoh: Mulai Jam ke-2"
                    class="bg-white/5 border border-white/10 text-white rounded-lg px-2 py-1.5 text-xs w-40 focus:outline-none focus:border-indigo-500"
                  />
                </div>
              </td>
            </tr>
            <tr v-if="teachers.length === 0">
              <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                <div class="text-4xl mb-3">😴</div>
                Tidak ada jadwal guru di shift ini.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  shift: String,
  hariIni: String,
  teachers: Array,
});

const page = usePage();

const toggleHadir = (guru) => {
  if (guru.status === 'HADIR') {
    guru.status = null;
    guru.keterangan_jam = null;
  } else {
    guru.status = 'HADIR';
    guru.keterangan_jam = null;
  }
  updateStatus(guru);
};

const updateStatus = (guru) => {
  router.post('/guru/tugas-piket/absensi', {
    id_guru: guru.id_guru,
    status: guru.status || 'LAINNYA',
    keterangan_jam: guru.keterangan_jam,
  }, {
    preserveScroll: true,
  });
};

const generateLaporanWa = () => {
  let text = `*Laporan Kehadiran Guru*\n*Tanggal:* ${props.hariIni}\n*Shift:* ${props.shift}\n\n`;
  
  props.teachers.forEach((guru, index) => {
    let checkmark = guru.status === 'HADIR' ? '✅' : '  ';
    let line = `${index + 1}. [${checkmark}] ${guru.nama_guru} (Jam ke-${guru.jam_ke})`;
    
    if (guru.status === 'SAKIT') line += ` - *Sakit*`;
    else if (guru.status === 'IZIN') line += ` - *Izin*`;
    else if (guru.status === 'LAINNYA' && guru.keterangan_jam) line += ` - *${guru.keterangan_jam}*`;
    
    text += line + '\n';
  });
  
  text += `\n*Petugas Piket:* ${/* Nama guru piket here */ 'Sistem Piket'}`;

  const encodedText = encodeURIComponent(text);
  // URL Encode to WhatsApp
  window.open(`https://wa.me/?text=${encodedText}`, '_blank');
};

const navigation = [
  {
    label: 'KBM (Kegiatan Belajar Mengajar)',
    items: [
      { href: '/guru/dashboard', icon: '📊', label: 'Dashboard' },
      { href: '/guru/jadwal', icon: '📅', label: 'Jadwal Mengajar' },
      { href: '/guru/riwayat-jurnal', icon: '📜', label: 'Riwayat Jurnal' },
    ],
  },
  {
    label: 'Tugas Khusus',
    items: [
      { href: '/guru/tugas-piket', icon: '👮', label: 'Tugas Piket' },
    ],
  },
];
</script>
