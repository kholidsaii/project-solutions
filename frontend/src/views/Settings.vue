<template>
  <div class="w-full min-h-screen bg-[#F4F6F9] flex flex-col md:flex-row overflow-x-hidden text-slate-800 font-sans pl-[80px] md:pl-[100px]">
    
    <aside class="w-full md:w-64 bg-white border-r border-slate-200 flex-shrink-0 flex flex-col min-h-screen">
      <div class="p-4 bg-slate-100 border-b border-slate-200 text-center">
        <h2 class="text-sm font-bold text-slate-800">Navigation</h2>
      </div>
      <ul class="flex-1 p-2 space-y-1">
        <li>
          <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-[#5c7cfa] bg-blue-50 rounded-lg">
            <i class="fas fa-folder text-lg"></i> Personal
          </a>
        </li>
        <li>
          <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 rounded-lg transition-colors">
            <i class="fas fa-folder text-slate-400 text-lg"></i> Organisasi
          </a>
        </li>
        <li>
          <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 rounded-lg transition-colors">
            <i class="fas fa-folder text-slate-400 text-lg"></i> Departement
          </a>
        </li>
        <li>
          <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 rounded-lg transition-colors">
            <i class="fas fa-folder text-slate-400 text-lg"></i> Label
          </a>
        </li>
      </ul>
    </aside>

    <main class="flex-1 p-4 md:p-6 overflow-y-auto">
      
      <div class="flex flex-wrap md:flex-nowrap items-center gap-3 mb-6 bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
        <button class="bg-[#5c7cfa] text-white px-5 py-2.5 rounded-lg flex items-center gap-2 font-bold text-sm shadow-sm hover:bg-blue-600 transition-colors">
          <i class="fas fa-home"></i> Teamwork
        </button>

        <div class="flex-1 relative min-w-[200px]">
          <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
          <input v-model="searchQuery" @input="fetchUsers" type="text" placeholder="Search..." class="w-full pl-11 pr-4 py-2.5 border border-slate-300 rounded-lg text-sm outline-none focus:border-[#5c7cfa] focus:ring-1 focus:ring-[#5c7cfa] transition-all" />
        </div>

        <button class="bg-white border border-slate-300 text-slate-600 px-5 py-2.5 rounded-lg flex items-center gap-2 font-semibold text-sm hover:bg-slate-50 transition-colors">
          <i class="fas fa-filter text-[#5c7cfa]"></i> Filter
        </button>

        <div class="flex border border-slate-300 rounded-lg overflow-hidden h-[42px]">
          <button class="px-4 bg-[#5c7cfa] text-white flex items-center justify-center"><i class="fas fa-bars"></i></button>
          <button class="px-4 bg-white text-slate-400 hover:text-[#5c7cfa] hover:bg-slate-50 flex items-center justify-center transition-colors"><i class="fas fa-th-large"></i></button>
        </div>

        <button @click="openModal('create')" class="bg-[#5c7cfa] text-white w-[42px] h-[42px] rounded-lg flex items-center justify-center font-bold text-lg shadow-sm hover:bg-blue-600 transition-all hover:scale-105 active:scale-95">
          <i class="fas fa-plus"></i>
        </button>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div v-for="u in users" :key="u.id" class="bg-white rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.05)] border border-slate-100 overflow-hidden flex flex-col group relative">
          
          <div class="h-28 w-full bg-slate-800 relative">
            <img src="https://images.unsplash.com/photo-1557683311-eac922347aa1?q=80&w=600&auto=format&fit=crop" alt="Banner" class="w-full h-full object-cover opacity-80 mix-blend-overlay">
          </div>

          <div class="relative flex justify-center -mt-10 mb-2">
            <div class="w-20 h-20 rounded-full border-[4px] border-white overflow-hidden bg-white shadow-sm relative z-10">
              <img :src="getAvatarUrl(u.avatar_url, u.name)" alt="Avatar" class="w-full h-full object-cover">
            </div>
            <div v-if="u.role === 'super_admin'" class="absolute bottom-1 ml-14 w-6 h-6 bg-[#2563EB] text-white rounded-full flex items-center justify-center border-2 border-white z-20 shadow-sm" title="Verified Owner">
              <i class="fas fa-check text-[10px]"></i>
            </div>
          </div>

          <div class="px-5 pt-2 pb-5 flex flex-col items-center flex-1">
            <span class="text-[10px] font-black uppercase tracking-wider mb-1" :class="getRoleTextColor(u.role)">
              {{ getRoleDisplay(u.role) }}
            </span>
            <h3 class="font-bold text-slate-800 text-base mb-2">{{ u.name }}</h3>
            <p class="text-[10px] text-slate-500 text-center leading-relaxed mb-4 px-2 line-clamp-4">
              <span class="font-semibold block mb-1 text-slate-600">{{ u.position || 'Staff Member' }} at {{ getCompanyName(u.company_id) }}</span>
              <span class="block mt-1 font-medium">{{ u.email }} • {{ u.phone || 'No Phone' }}</span>
            </p>

            <div class="mt-auto w-full">
              
              <div class="flex items-center justify-center gap-5 text-sm mb-4">
                <a v-if="u.facebook" :href="u.facebook" target="_blank" class="text-[#2563EB] hover:scale-110 transition-transform"><i class="fab fa-facebook-f"></i></a>
                <i v-else class="fab fa-facebook-f text-slate-200" title="Belum diatur"></i>

                <a v-if="u.twitter" :href="u.twitter" target="_blank" class="text-[#38BDF8] hover:scale-110 transition-transform"><i class="fab fa-twitter"></i></a>
                <i v-else class="fab fa-twitter text-slate-200" title="Belum diatur"></i>

                <a v-if="u.instagram" :href="u.instagram" target="_blank" class="text-[#E1306C] hover:scale-110 transition-transform"><i class="fab fa-instagram"></i></a>
                <i v-else class="fab fa-instagram text-slate-200" title="Belum diatur"></i>

                <a v-if="u.linkedin" :href="u.linkedin" target="_blank" class="text-[#1D4ED8] hover:scale-110 transition-transform"><i class="fab fa-linkedin-in"></i></a>
                <i v-else class="fab fa-linkedin-in text-slate-200" title="Belum diatur"></i>
              </div>

              <div class="flex border-t border-slate-100 pt-3 gap-2">
                <button @click="openModal('edit', u)" class="flex-1 py-1.5 text-[10px] font-bold text-[#5c7cfa] hover:bg-blue-50 rounded-lg transition-colors uppercase tracking-wider">Edit</button>
                <button @click="deleteUser(u.id)" :disabled="u.id === currentUser.id" class="flex-1 py-1.5 text-[10px] font-bold text-rose-500 hover:bg-rose-50 rounded-lg transition-colors uppercase tracking-wider disabled:opacity-30 disabled:hover:bg-transparent">Hapus</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <div v-if="isModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 animate-in fade-in duration-300">
      <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
        
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
          <h2 class="text-lg font-black italic uppercase text-slate-900">
            {{ modalMode === 'create' ? 'Registrasi Personel Baru' : 'Edit Personel & Akses' }}
          </h2>
          <button @click="closeModal" class="text-slate-400 hover:text-rose-500"><i class="fas fa-times text-xl"></i></button>
        </div>

        <div class="p-6 overflow-y-auto flex-1 custom-scrollbar">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            
            <div class="space-y-4">
              <h3 class="text-[10px] font-black text-[#5c7cfa] uppercase tracking-widest border-b pb-2 mb-4">Profil & Kontak</h3>
              
              <div class="flex items-center gap-4 mb-4">
                <div class="w-16 h-16 rounded-2xl border-2 border-dashed border-slate-300 flex items-center justify-center relative overflow-hidden bg-slate-50">
                  <img v-if="avatarPreview" :src="avatarPreview" class="w-full h-full object-cover">
                  <i v-else class="fas fa-camera text-slate-300 text-xl"></i>
                  <input type="file" @change="handleAvatarSelect" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*">
                </div>
                <div>
                  <p class="text-[10px] font-bold text-slate-700">Foto Personel</p>
                  <p class="text-[9px] text-slate-400">Upload JPG/PNG max 2MB.</p>
                </div>
              </div>

              <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase mb-1 block">Nama Lengkap *</label>
                <input v-model="formUser.name" type="text" class="w-full p-3 bg-white border border-slate-200 rounded-xl outline-none focus:border-[#5c7cfa] focus:ring-1 focus:ring-[#5c7cfa] font-semibold text-xs" />
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="text-[10px] font-bold text-slate-500 uppercase mb-1 block">Email (Login) *</label>
                  <input v-model="formUser.email" type="email" class="w-full p-3 bg-white border border-slate-200 rounded-xl outline-none focus:border-[#5c7cfa] focus:ring-1 focus:ring-[#5c7cfa] font-semibold text-xs" />
                </div>
                <div>
                  <label class="text-[10px] font-bold text-slate-500 uppercase mb-1 block">Password {{ modalMode === 'edit' ? '(Opsional)' : '*' }}</label>
                  <input v-model="formUser.password" type="password" placeholder="********" class="w-full p-3 bg-white border border-slate-200 rounded-xl outline-none focus:border-[#5c7cfa] focus:ring-1 focus:ring-[#5c7cfa] font-semibold text-xs" />
                </div>
              </div>
              
              <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase mb-1 block">No Telepon / WhatsApp</label>
                <input v-model="formUser.phone" type="text" placeholder="0812..." class="w-full p-3 bg-white border border-slate-200 rounded-xl outline-none focus:border-[#5c7cfa] focus:ring-1 focus:ring-[#5c7cfa] font-semibold text-xs" />
              </div>

              <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase mb-1 block">Alamat Lengkap</label>
                <textarea v-model="formUser.address" rows="2" placeholder="Alamat domisili..." class="w-full p-3 bg-white border border-slate-200 rounded-xl outline-none focus:border-[#5c7cfa] focus:ring-1 focus:ring-[#5c7cfa] font-semibold text-xs resize-none"></textarea>
              </div>

              <h3 class="text-[10px] font-black text-[#5c7cfa] uppercase tracking-widest border-b pb-2 mb-4 mt-6">Media Sosial (Opsional)</h3>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="text-[10px] font-bold text-slate-500 uppercase mb-1 block">Facebook</label>
                  <input v-model="formUser.facebook" type="text" placeholder="https://fb.com/..." class="w-full p-3 bg-white border border-slate-200 rounded-xl outline-none focus:border-[#5c7cfa] focus:ring-1 focus:ring-[#5c7cfa] font-semibold text-xs" />
                </div>
                <div>
                  <label class="text-[10px] font-bold text-slate-500 uppercase mb-1 block">Twitter / X</label>
                  <input v-model="formUser.twitter" type="text" placeholder="https://x.com/..." class="w-full p-3 bg-white border border-slate-200 rounded-xl outline-none focus:border-[#5c7cfa] focus:ring-1 focus:ring-[#5c7cfa] font-semibold text-xs" />
                </div>
                <div>
                  <label class="text-[10px] font-bold text-slate-500 uppercase mb-1 block">Instagram</label>
                  <input v-model="formUser.instagram" type="text" placeholder="https://ig.com/..." class="w-full p-3 bg-white border border-slate-200 rounded-xl outline-none focus:border-[#5c7cfa] focus:ring-1 focus:ring-[#5c7cfa] font-semibold text-xs" />
                </div>
                <div>
                  <label class="text-[10px] font-bold text-slate-500 uppercase mb-1 block">LinkedIn</label>
                  <input v-model="formUser.linkedin" type="text" placeholder="https://linkedin.com/..." class="w-full p-3 bg-white border border-slate-200 rounded-xl outline-none focus:border-[#5c7cfa] focus:ring-1 focus:ring-[#5c7cfa] font-semibold text-xs" />
                </div>
              </div>
            </div>

            <div class="space-y-4">
              <h3 class="text-[10px] font-black text-[#5c7cfa] uppercase tracking-widest border-b pb-2 mb-4">Pekerjaan & Akses</h3>
              
              <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase mb-1 block">Mapping Entitas (PT)</label>
                <select v-model="formUser.company_id" class="w-full p-3 bg-white border border-slate-200 rounded-xl outline-none focus:border-[#5c7cfa] focus:ring-1 focus:ring-[#5c7cfa] font-semibold text-xs text-slate-700">
                  <option :value="null">-- Tidak Terikat (Independen) --</option>
                  <option v-for="pt in companies" :key="pt.id" :value="pt.id">{{ pt.name }}</option>
                </select>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="text-[10px] font-bold text-slate-500 uppercase mb-1 block">Jabatan / Posisi</label>
                  <input v-model="formUser.position" type="text" placeholder="CTO, Manager..." class="w-full p-3 bg-white border border-slate-200 rounded-xl outline-none focus:border-[#5c7cfa] focus:ring-1 focus:ring-[#5c7cfa] font-semibold text-xs" />
                </div>
                <div>
                  <label class="text-[10px] font-bold text-slate-500 uppercase mb-1 block">Role Sistem *</label>
                  <select v-model="formUser.role" class="w-full p-3 bg-white border border-slate-200 rounded-xl outline-none focus:border-[#5c7cfa] focus:ring-1 focus:ring-[#5c7cfa] font-semibold text-xs text-slate-700">
                    <option value="super_admin">Super Admin (Owner)</option>
                    <option value="admin">Admin / Finance</option>
                    <option value="project_manager">Project Manager</option>
                    <option value="member">Team Member</option>
                  </select>
                </div>
              </div>

              <h3 class="text-[10px] font-black text-[#5c7cfa] uppercase tracking-widest border-b pb-2 mb-4 mt-6">Informasi Finansial</h3>
              
              <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase mb-1 block">Rate Gaji per Jam (Rp)</label>
                <input v-model="formUser.hourly_rate" type="number" placeholder="0" class="w-full p-3 bg-white border border-slate-200 rounded-xl outline-none focus:border-[#5c7cfa] focus:ring-1 focus:ring-[#5c7cfa] font-semibold text-xs" />
              </div>

              <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                <div>
                  <label class="text-[10px] font-bold text-slate-500 uppercase mb-1 block">Nama Bank</label>
                  <input v-model="formUser.bank_name" type="text" placeholder="BCA, Mandiri..." class="w-full p-2.5 bg-white border border-slate-200 rounded-lg outline-none focus:border-[#5c7cfa] focus:ring-1 focus:ring-[#5c7cfa] font-semibold text-xs" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="text-[10px] font-bold text-slate-500 uppercase mb-1 block">Atas Nama</label>
                    <input v-model="formUser.account_name" type="text" placeholder="Nama Pemilik" class="w-full p-2.5 bg-white border border-slate-200 rounded-lg outline-none focus:border-[#5c7cfa] focus:ring-1 focus:ring-[#5c7cfa] font-semibold text-xs" />
                  </div>
                  <div>
                    <label class="text-[10px] font-bold text-slate-500 uppercase mb-1 block">No Rekening</label>
                    <input v-model="formUser.account_number" type="text" placeholder="123456789" class="w-full p-2.5 bg-white border border-slate-200 rounded-lg outline-none focus:border-[#5c7cfa] focus:ring-1 focus:ring-[#5c7cfa] font-semibold text-xs" />
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

        <div class="p-6 border-t border-slate-100 bg-slate-50/50 flex gap-3 justify-end rounded-b-2xl">
          <button @click="closeModal" class="px-6 py-2.5 bg-white border border-slate-300 text-slate-600 rounded-lg font-bold text-xs hover:bg-slate-50 transition-colors">Batal</button>
          <button @click="saveUser" class="px-8 py-2.5 bg-[#5c7cfa] text-white rounded-lg font-bold text-xs shadow-sm hover:bg-blue-600 transition-all flex items-center gap-2">
            <i class="fas fa-save"></i> Simpan
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../api/axios';

const currentUser = ref(JSON.parse(localStorage.getItem('user_info') || '{}'));
const users = ref<any[]>([]);
const companies = ref<any[]>([]);
const searchQuery = ref('');

// Setup Modal User
const isModalOpen = ref(false);
const modalMode = ref('create'); 
const avatarPreview = ref<string | null>(null);
const selectedAvatarFile = ref<File | null>(null);

const formUser = ref({
  id: null as number | null,
  name: '',
  email: '',
  password: '',
  phone: '',
  address: '',
  position: '',
  role: 'member',
  company_id: null as number | null,
  hourly_rate: 0,
  bank_name: '',
  account_name: '',
  account_number: '',
  facebook: '', 
  twitter: '', 
  instagram: '', 
  linkedin: ''
});

const baseUrl = (import.meta.env.VITE_API_URL || 'http://localhost:8000').replace('/api', '');

const fetchUsers = async () => {
  try {
    const res = await api.get('/users', { params: { tag_search: searchQuery.value } });
    users.value = res.data.data || res.data; 
  } catch (e) {
    console.error("Gagal ambil data user", e);
  }
};

const fetchCompanies = async () => {
  try {
    const res = await api.get('/companies');
    companies.value = res.data.data || res.data;
  } catch (e) {
    console.error("Gagal ambil perusahaan", e);
  }
};

const getCompanyName = (id: number) => {
  if (!id) return 'Independent';
  const pt = companies.value.find(c => c.id === id);
  return pt ? pt.name : 'Unknown PT';
};

const getAvatarUrl = (path: string | null, name: string) => {
  if (path) return `${baseUrl}/uploads/${path}`;
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=f1f5f9&color=475569`;
};

const getRoleTextColor = (role: string) => {
  switch(role) {
    case 'super_admin': return 'text-rose-500';
    case 'admin': return 'text-blue-500';
    case 'project_manager': return 'text-amber-500';
    case 'member': default: return 'text-emerald-500';
  }
};

const getRoleDisplay = (role: string) => {
  switch(role) {
    case 'super_admin': return 'owner';
    case 'admin': return 'admin';
    case 'project_manager': return 'manager';
    case 'member': default: return 'member';
  }
};

const openModal = (mode: string, user: any = null) => {
  modalMode.value = mode;
  selectedAvatarFile.value = null;
  
  if (mode === 'edit' && user) {
    formUser.value = {
      id: user.id,
      name: user.name,
      email: user.email,
      password: '', 
      phone: user.phone || '',
      address: user.address || '',
      position: user.position || '',
      role: user.role || 'member',
      company_id: user.company_id || null,
      hourly_rate: user.hourly_rate || 0,
      bank_name: user.bank_name || '',
      account_name: user.account_name || '',
      account_number: user.account_number || '',
      facebook: user.facebook || '',
      twitter: user.twitter || '',
      instagram: user.instagram || '',
      linkedin: user.linkedin || ''
    };
    avatarPreview.value = user.avatar_url ? `${baseUrl}/uploads/${user.avatar_url}` : null;
  } else {
    formUser.value = { id: null, name: '', email: '', password: '', phone: '', address: '', position: '', role: 'member', company_id: null, hourly_rate: 0, bank_name: '', account_name: '', account_number: '', facebook: '', twitter: '', instagram: '', linkedin: '' };
    avatarPreview.value = null;
  }
  isModalOpen.value = true;
};

const closeModal = () => { isModalOpen.value = false; };

const handleAvatarSelect = (e: any) => {
  const file = e.target.files[0];
  if (file) {
    selectedAvatarFile.value = file;
    const reader = new FileReader();
    reader.onload = (event: any) => { avatarPreview.value = event.target.result; };
    reader.readAsDataURL(file);
  }
};

const saveUser = async () => {
  if (!formUser.value.name || !formUser.value.email) return alert("Nama dan Email wajib diisi!");
  if (modalMode.value === 'create' && !formUser.value.password) return alert("Password wajib diisi untuk user baru!");

  const formData = new FormData();
  formData.append('name', formUser.value.name);
  formData.append('email', formUser.value.email);
  formData.append('role', formUser.value.role);
  if (formUser.value.password) formData.append('password', formUser.value.password);
  if (formUser.value.phone) formData.append('phone', formUser.value.phone);
  if (formUser.value.address) formData.append('address', formUser.value.address);
  if (formUser.value.position) formData.append('position', formUser.value.position);
  if (formUser.value.company_id) formData.append('company_id', formUser.value.company_id.toString());
  if (formUser.value.hourly_rate) formData.append('hourly_rate', formUser.value.hourly_rate.toString());
  
  if (formUser.value.bank_name) formData.append('bank_name', formUser.value.bank_name);
  if (formUser.value.account_name) formData.append('account_name', formUser.value.account_name);
  if (formUser.value.account_number) formData.append('account_number', formUser.value.account_number);
  if (formUser.value.facebook) formData.append('facebook', formUser.value.facebook);
  if (formUser.value.twitter) formData.append('twitter', formUser.value.twitter);
  if (formUser.value.instagram) formData.append('instagram', formUser.value.instagram);
  if (formUser.value.linkedin) formData.append('linkedin', formUser.value.linkedin);
  
  if (selectedAvatarFile.value) formData.append('avatar', selectedAvatarFile.value);

  try {
    if (modalMode.value === 'create') {
      await api.post('/users/register', formData, { headers: { 'Content-Type': 'multipart/form-data' } });
    } else {
      formData.append('_method', 'PUT');
      await api.post(`/users/${formUser.value.id}`, formData, { headers: { 'Content-Type': 'multipart/form-data' } });
    }
    closeModal();
    fetchUsers();
  } catch (e) {
    alert("Gagal menyimpan data user!");
    console.error(e);
  }
};

const deleteUser = async (id: number) => {
  if (confirm('Yakin ingin menghapus personel ini? Semua akses dan data terkait mungkin terpengaruh.')) {
    try { 
      await api.delete(`/users/${id}`); 
      fetchUsers(); 
    } catch (e: any) { 
      alert(e.response?.data?.error || "Gagal menghapus user!"); 
    }
  }
};

onMounted(() => {
  fetchCompanies();
  fetchUsers();
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>