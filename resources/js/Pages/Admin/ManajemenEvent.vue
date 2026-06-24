<template>
  <Head title="Manajemen Event & Dispensasi KBM" />

  <AppLayout
    title="Manajemen Event & Dispensasi"
    subtitle="Atur hari atau jam spesifik di mana KBM ditiadakan agar guru tidak tercatat Alpa."
    :navigation="navigation"
  >
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <!-- FORM TAMBAH EVENT -->
      <div class="lg:col-span-1">
        <div class="rounded-2xl border border-white/8 p-5" style="background: var(--card)">
          <div class="font-bold text-sm mb-4">Tambah Event / Dispensasi Baru</div>
          
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Nama Event</label>
              <input v-model="form.nama_event" type="text" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500" placeholder="Contoh: Class Meeting, Rapat Guru" required>
            </div>
            
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Tanggal</label>
              <input v-model="form.tanggal" type="date" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500" required>
            </div>
            
            <div class="grid grid-cols-2 gap-3">
              <div class="col-span-2">
                <label class="block text-xs font-semibold text-slate-400 mb-1">Target Shift</label>
                <select v-model="form.target_shift" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
                  <option value="SEMUA">Semua Shift</option>
                  <option value="PAGI">Shift Pagi Saja</option>
                  <option value="SIANG">Shift Siang Saja</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Target Jam Ke- <span class="text-[10px] text-slate-500 font-normal">(Kosongkan jika Full 1 Hari)</span></label>
              <div class="flex flex-wrap gap-2 mt-2">
                <label v-for="j in 10" :key="j" class="flex items-center gap-1.5 bg-white/5 border border-white/10 rounded px-2 py-1 cursor-pointer hover:bg-white/10 transition-colors">
                  <input type="checkbox" :value="j" v-model="form.jam_ke" class="rounded border-white/20 bg-transparent text-indigo-500 focus:ring-indigo-500">
                  <span class="text-xs font-mono">{{ j }}</span>
                </label>
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Target Kelas <span class="text-[10px] text-slate-500 font-normal">(Kosongkan jika Semua Kelas)</span></label>
              <select v-model="form.target_kelas" multiple class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500 h-32">
                <option v-for="c in classes" :key="c.id_kelas" :value="c.id_kelas">
                  {{ c.nama_kelas }}
                </option>
              </select>
              <div class="text-[10px] text-slate-500 mt-1">Tahan tombol CTRL (Windows) atau CMD (Mac) untuk memilih lebih dari satu kelas.</div>
            </div>

            <button type="submit" :disabled="form.processing" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold py-2.5 rounded-lg transition-colors mt-4">
              Simpan Event
            </button>
          </form>
        </div>
      </div>

      <!-- DAFTAR EVENT -->
      <div class="lg:col-span-2">
        <div class="rounded-2xl border border-white/8 overflow-hidden" style="background: var(--card)">
          <div class="flex items-center justify-between px-5 py-4 border-b border-white/8">
            <div class="font-bold text-sm">Daftar Event / Dispensasi Mendatang</div>
          </div>
          
          <div v-if="events.length === 0" class="p-8 text-center text-slate-500 text-sm">
            Belum ada event yang didaftarkan.
          </div>
          
          <table v-else class="w-full">
            <thead>
              <tr class="bg-white/2 border-b border-white/8">
                <th class="text-left px-4 py-3 text-[10px] font-semibold text-slate-600 uppercase tracking-wider">Tanggal</th>
                <th class="text-left px-4 py-3 text-[10px] font-semibold text-slate-600 uppercase tracking-wider">Nama Event</th>
                <th class="text-left px-4 py-3 text-[10px] font-semibold text-slate-600 uppercase tracking-wider">Target</th>
                <th class="text-left px-4 py-3 text-[10px] font-semibold text-slate-600 uppercase tracking-wider">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="event in events" :key="event.id" class="border-b border-white/4 hover:bg-white/2 transition-colors">
                <td class="px-4 py-3 text-sm font-semibold">{{ formatDate(event.tanggal) }}</td>
                <td class="px-4 py-3 text-sm">{{ event.nama_event }}</td>
                <td class="px-4 py-3 text-xs text-slate-400">
                  <div class="mb-1"><span class="font-semibold text-slate-300">Shift:</span> {{ event.target_shift || 'Semua' }}</div>
                  <div class="mb-1"><span class="font-semibold text-slate-300">Jam:</span> {{ formatJam(event.jam_ke) }}</div>
                  <div><span class="font-semibold text-slate-300">Kelas:</span> {{ formatKelas(event.target_kelas) }}</div>
                </td>
                <td class="px-4 py-3">
                  <button @click="deleteEvent(event.id)" class="text-xs text-red-400 hover:text-red-300 font-semibold px-2 py-1 rounded bg-red-500/10 hover:bg-red-500/20 transition-colors">
                    Hapus
                  </button>
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
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  events: Array,
  classes: Array
});

// Menambahkan menu navigasi admin
const navigation = [
  {
    label: 'Utama',
    items: [
      { href: '/admin/dashboard', icon: '📊', label: 'Dashboard' },
    ],
  },
  {
    label: 'Sekolah',
    items: [
      { href: '/admin/wali-kelas', icon: '👤', label: 'Wali Kelas' },
      { href: '/admin/siswa', icon: '👥', label: 'Data Siswa' },
      { href: '/admin/events', icon: '🗓️', label: 'Event & Libur' },
      { href: '/admin/kuesioner', icon: '⭐', label: 'Kuesioner Kinerja' },
    ],
  },
  {
    label: 'Konfigurasi',
    items: [
      { href: '/admin/jadwal-jp', icon: '⏰', label: 'Jadwal JP' },
      { href: '/admin/grade-config', icon: '⚖️', label: 'Bobot Nilai' },
      { href: '/admin/timeline', icon: '📅', label: 'Timeline' },
    ],
  },
  {
    label: 'Laporan',
    items: [
      { href: '/admin/monitoring-nilai', icon: '📈', label: 'Monitoring Nilai' },
      { href: '/admin/rapor', icon: '📋', label: 'Ekspor Rapor' },
      { href: '/admin/laporan-performa', icon: '🏆', label: 'Laporan Performa' },
      { href: '/admin/jurnal', icon: '📓', label: 'Jurnal Tatap Muka' },
    ],
  }
];

const form = useForm({
  nama_event: '',
  tanggal: '',
  target_shift: 'SEMUA',
  jam_ke: [],
  target_kelas: []
});

const submit = () => {
  form.post('/admin/event', {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
};

const deleteEvent = (id) => {
  if (confirm('Yakin ingin menghapus event ini?')) {
    router.delete(`/admin/event/${id}`, { preserveScroll: true });
  }
};

const formatDate = (dateStr) => {
  return new Date(dateStr).toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
};

const formatJam = (jamArr) => {
  if (!jamArr || jamArr.length === 0) return 'Full 1 Hari';
  return jamArr.sort((a,b) => a-b).join(', ');
};

const formatKelas = (kelasIds) => {
  if (!kelasIds || kelasIds.length === 0) return 'Seluruh Kelas';
  
  // Ambil nama kelas berdasarkan ID
  const namaKelas = kelasIds.map(id => {
    const cls = props.classes.find(c => c.id_kelas == id);
    return cls ? cls.nama_kelas : id;
  });
  
  if (namaKelas.length <= 3) return namaKelas.join(', ');
  return `${namaKelas.slice(0, 3).join(', ')}... (+${namaKelas.length - 3} lainnya)`;
};
</script>
