<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../../api/axios';

const props = defineProps(['project']);

// --- STATE CRUD LOCATION ---
const locations = ref<any[]>([]);
const showModal = ref(false);
const isEditing = ref(false);
const locForm = ref({ id: null as number | null, name: '', address: '' });

// Fetch Data Lokasi dari Backend
const fetchLocations = async () => {
  try {
    const res = await api.get('/master-data/locations');
    locations.value = res.data;
  } catch (e) {
    console.error("Gagal load lokasi", e);
  }
};

onMounted(() => {
  fetchLocations();
});

// Aksi Buka Form Add
const openAdd = () => {
  isEditing.value = false;
  locForm.value = { id: null, name: '', address: '' };
  showModal.value = true;
};

// Aksi Buka Form Edit
const editLoc = (loc: any) => {
  isEditing.value = true;
  locForm.value = { id: loc.id, name: loc.name, address: loc.address };
  showModal.value = true;
};

// Aksi Simpan Data
const saveLoc = async () => {
  if (!locForm.value.name) return alert("Nama Lokasi wajib diisi!");
  try {
    if (isEditing.value) {
      await api.put(`/master-data/locations/${locForm.value.id}`, locForm.value);
    } else {
      await api.post('/master-data/locations', locForm.value);
    }
    showModal.value = false;
    fetchLocations();
  } catch (e) {
    console.error(e);
  }
};

// Aksi Hapus Data
const deleteLoc = async (id: number) => {
  if (!confirm("Hapus lokasi ini? Aktivitas yang terhubung ke lokasi ini akan kehilangan datanya.")) return;
  try {
    await api.delete(`/master-data/locations/${id}`);
    fetchLocations();
  } catch (e) {
    console.error(e);
  }
};
</script>

<template>
  <div class="space-y-6 animate-in slide-in-from-right-4 duration-500 relative">
    
    <div class="bg-indigo-900 p-6 rounded-3xl text-white flex justify-between items-center shadow-xl">
      <div>
        <h4 class="text-sm font-black uppercase tracking-widest">Site Location Database</h4>
        <p class="text-[9px] font-bold opacity-60 uppercase mt-1">Manage Work Locations & Address</p>
      </div>
      <button @click="openAdd" class="bg-white text-indigo-900 px-5 py-2.5 rounded-xl text-[10px] font-black uppercase shadow-lg hover:scale-95 transition-transform flex items-center gap-2">
        <i class="fas fa-plus"></i> Add Location
      </button>
    </div>

    <div class="w-full space-y-4">
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="loc in locations" :key="loc.id" class="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm group hover:border-indigo-300 transition-all flex flex-col justify-between gap-4">
          
          <div>
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                 <i class="fas fa-map-marker-alt text-lg"></i>
              </div>
              <div class="min-w-0">
                 <h4 class="text-[12px] font-black text-slate-800 uppercase leading-tight truncate">{{ loc.name }}</h4>
                 <span class="text-[8px] font-bold text-slate-400 uppercase tracking-widest">ID-{{ loc.id }}</span>
              </div>
            </div>
            <p class="text-[10px] font-medium text-slate-500 leading-relaxed bg-slate-50 p-3 rounded-xl border border-slate-100 break-words">
              <i class="fas fa-map text-[9px] mr-1 text-slate-400"></i> {{ loc.address || 'Belum ada alamat lengkap' }}
            </p>
          </div>
          
          <div class="flex items-center gap-2 justify-end pt-3 border-t border-slate-50 mt-auto shrink-0">
               <button @click="editLoc(loc)" class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all flex items-center justify-center"><i class="fas fa-edit text-[10px]"></i></button>
               <button @click="deleteLoc(loc.id)" class="w-8 h-8 rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition-all flex items-center justify-center"><i class="fas fa-trash-alt text-[10px]"></i></button>
          </div>

        </div>
      </div>

      <div v-if="locations.length === 0" class="w-full bg-slate-50 border-2 border-dashed border-slate-200 rounded-[2.5rem] p-10 flex flex-col items-center justify-center grayscale opacity-50">
        <i class="fas fa-map-marked-alt text-4xl mb-4 text-indigo-300"></i>
        <p class="text-[10px] font-black text-slate-400 uppercase">No Master Locations Added</p>
      </div>
      
    </div>

    <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl overflow-hidden animate-in zoom-in duration-300">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
          <h4 class="text-xs font-black uppercase text-indigo-900 tracking-widest">{{ isEditing ? 'Edit' : 'Add New' }} Location</h4>
          <button @click="showModal = false" class="w-8 h-8 bg-slate-100 hover:bg-slate-200 rounded-full flex items-center justify-center text-slate-500 transition-colors"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="p-8 space-y-5">
          <div class="space-y-1.5">
            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Location Name / Site</label>
            <input v-model="locForm.name" type="text" placeholder="E.G. SITE PROYEK JAKARTA SELATAN" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-bold text-slate-700 outline-none focus:border-indigo-300 uppercase">
          </div>
          
          <div class="space-y-1.5">
            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Full Address</label>
            <textarea v-model="locForm.address" rows="4" placeholder="JL. SUDIRMAN NO 123..." class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-medium text-slate-700 outline-none focus:border-indigo-300 uppercase resize-none"></textarea>
          </div>
        </div>
        
        <div class="p-6 bg-slate-50 flex gap-3 border-t border-slate-100">
          <button @click="saveLoc" class="flex-1 bg-indigo-600 text-white py-4 rounded-2xl text-[11px] font-black uppercase tracking-widest shadow-xl shadow-indigo-200 hover:bg-indigo-700 active:scale-95 transition-all">Save Location</button>
        </div>
      </div>
    </div>

  </div>
</template>