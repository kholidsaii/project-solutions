<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../api/axios';

// State Data
const users = ref<any[]>([]);
const isLoading = ref(true);
const isSubmitting = ref(false);
const showModal = ref(false);

// Form Data Member Baru
const form = ref({
  name: '',
  email: '',
  password: '',
  role: 'member'
});

// Ambil Data User dari API
const fetchUsers = async () => {
  isLoading.value = true;
  try {
    const res = await api.get('/users');
    users.value = res.data;
  } catch (error) {
    console.error("Gagal ambil user:", error);
  } finally {
    setTimeout(() => { isLoading.value = false; }, 500);
  }
};

// Proses Registrasi User
const handleRegister = async () => {
  isSubmitting.value = true;
  try {
    await api.post('/users/register', form.value);
    
    // Reset & Close
    showModal.value = false;
    form.value = { name: '', email: '', password: '', role: 'member' };
    
    // Refresh list data
    await fetchUsers();
    alert('Member berhasil didaftarkan!');
  } catch (error: any) {
    alert(error.response?.data?.message || 'Gagal mendaftarkan member');
  } finally {
    isSubmitting.value = false;
  }
};

// Styling Role Badge
const getRoleBadge = (role: string) => {
  switch (role) {
    case 'super_admin': return 'bg-rose-50 text-rose-600 border-rose-100';
    case 'admin': return 'bg-indigo-50 text-indigo-600 border-indigo-100';
    default: return 'bg-slate-50 text-slate-600 border-slate-100';
  }
};

onMounted(fetchUsers);
</script>

<template>
  <div class="p-4 md:p-10 min-h-screen bg-slate-50">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-12">
      <div class="flex items-center gap-6">
        <div class="w-16 h-16 bg-indigo-600 rounded-3xl shadow-xl shadow-indigo-200 flex items-center justify-center text-white text-2xl rotate-3">
          <i class="fas fa-users"></i>
        </div>
        <div>
          <h1 class="text-3xl font-black text-indigo-950 italic uppercase leading-none tracking-tighter">Teamwork</h1>
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-2">Internal Access Control</p>
        </div>
      </div>
      
      <button @click="showModal = true" class="bg-indigo-600 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
        <i class="fas fa-plus mr-2"></i> Register Member
      </button>
    </div>

    <div class="bg-white rounded-[3rem] shadow-xl shadow-slate-200/40 border border-white overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50/50">
              <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Member Info</th>
              <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Email Address</th>
              <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Role</th>
              <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-if="isLoading" v-for="i in 3" :key="i">
               <td colspan="4" class="px-8 py-6"><div class="h-12 bg-slate-100 animate-pulse rounded-xl w-full"></div></td>
            </tr>
            
            <tr v-else v-for="user in users" :key="user.id" class="hover:bg-slate-50/50 transition-colors group">
              <td class="px-8 py-6">
                <div class="flex items-center gap-4">
                  <div class="w-12 h-12 rounded-2xl overflow-hidden border-2 border-slate-50 shadow-sm bg-indigo-50 flex items-center justify-center text-indigo-600 font-black">
                    <img v-if="user.avatar" :src="user.avatar" class="w-full h-full object-cover">
                    <span v-else>{{ user.name.substring(0, 2).toUpperCase() }}</span>
                  </div>
                  <div>
                    <p class="text-sm font-black text-slate-700 uppercase italic">{{ user.name }}</p>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Joined {{ new Date(user.created_at).toLocaleDateString('id-ID') }}</p>
                  </div>
                </div>
              </td>
              <td class="px-8 py-6 text-xs font-bold text-slate-500 lowercase">{{ user.email }}</td>
              <td class="px-8 py-6">
                <span :class="getRoleBadge(user.role)" class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase italic border">
                  {{ user.role.replace('_', ' ') }}
                </span>
              </td>
              <td class="px-8 py-6 text-right">
                <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button class="w-10 h-10 rounded-xl bg-slate-50 text-slate-400 hover:bg-indigo-600 hover:text-white transition-all">
                    <i class="fas fa-edit text-xs"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-indigo-950/40 backdrop-blur-sm" @click="showModal = false"></div>
      
      <div class="bg-white w-full max-w-md rounded-[3rem] p-10 shadow-2xl relative z-10 border border-white">
        <h2 class="text-2xl font-black text-indigo-950 uppercase italic mb-2">New Member</h2>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-8">Add access for your team</p>

        <form @submit.prevent="handleRegister" class="space-y-5">
          <div>
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">Full Name</label>
            <input v-model="form.name" type="text" required placeholder="e.g. Daffa Developer" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl text-sm font-bold focus:ring-2 focus:ring-indigo-100 outline-none mt-2">
          </div>
          
          <div>
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">Email Address</label>
            <input v-model="form.email" type="email" required placeholder="daffa@kerjapro.com" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl text-sm font-bold focus:ring-2 focus:ring-indigo-100 outline-none mt-2">
          </div>

          <div>
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">Password</label>
            <input v-model="form.password" type="password" required placeholder="••••••••" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl text-sm font-bold focus:ring-2 focus:ring-indigo-100 outline-none mt-2">
          </div>

          <div>
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">Access Role</label>
            <div class="relative mt-2">
              <select v-model="form.role" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl text-sm font-bold focus:ring-2 focus:ring-indigo-100 outline-none appearance-none cursor-pointer uppercase">
                <option value="member">MEMBER</option>
                <option value="admin">ADMIN</option>
                <option value="super_admin">SUPER ADMIN</option>
              </select>
              <i class="fas fa-chevron-down absolute right-6 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none text-xs"></i>
            </div>
          </div>

          <div class="flex gap-4 pt-6">
            <button type="button" @click="showModal = false" class="flex-1 py-4 bg-slate-100 text-slate-400 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 transition-all">Cancel</button>
            <button type="submit" :disabled="isSubmitting" class="flex-1 py-4 bg-indigo-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all disabled:opacity-50">
              {{ isSubmitting ? 'Processing...' : 'Confirm' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
@reference "../style.css";
</style>