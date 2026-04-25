<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Portfolio Showcase</h1>
          <p class="text-gray-600 mt-1">Upload work samples, projects, and certificates to showcase your expertise</p>
        </div>
        <div class="flex items-center gap-3">
          <Button 
            icon="pi pi-eye" 
            label="Preview Public" 
            outlined 
            @click="previewPublic"
            :disabled="loading"
          />
          <Button 
            icon="pi pi-plus" 
            label="Add Item" 
            @click="showAddModal = true"
            :disabled="loading"
          />
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
              <i class="pi pi-folder text-blue-600"></i>
            </div>
            <div>
              <div class="text-2xl font-bold text-gray-900">{{ portfolioItems.length }}</div>
              <div class="text-sm text-gray-600">Total Items</div>
            </div>
          </div>
        </div>
        
        <div class="bg-white rounded-lg border border-gray-200 p-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
              <i class="pi pi-globe text-green-600"></i>
            </div>
            <div>
              <div class="text-2xl font-bold text-gray-900">{{ publicItems.length }}</div>
              <div class="text-sm text-gray-600">Public Items</div>
            </div>
          </div>
        </div>
        
        <div class="bg-white rounded-lg border border-gray-200 p-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
              <i class="pi pi-star text-purple-600"></i>
            </div>
            <div>
              <div class="text-2xl font-bold text-gray-900">{{ featuredItems.length }}</div>
              <div class="text-sm text-gray-600">Featured</div>
            </div>
          </div>
        </div>
        
        <div class="bg-white rounded-lg border border-gray-200 p-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
              <i class="pi pi-eye text-amber-600"></i>
            </div>
            <div>
              <div class="text-2xl font-bold text-gray-900">{{ totalViews }}</div>
              <div class="text-sm text-gray-600">Total Views</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex flex-wrap items-center gap-4">
          <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700">Type:</label>
            <Dropdown 
              v-model="filters.type" 
              :options="typeOptions" 
              optionLabel="label"
              optionValue="value"
              placeholder="All Types"
              class="w-40"
              @change="filterItems"
            />
          </div>
          <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700">Category:</label>
            <Dropdown 
              v-model="filters.category" 
              :options="categoryOptions" 
              optionLabel="label"
              optionValue="value"
              placeholder="All Categories"
              class="w-48"
              @change="filterItems"
            />
          </div>
          <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700">Status:</label>
            <Dropdown 
              v-model="filters.status" 
              :options="statusOptions" 
              optionLabel="label"
              optionValue="value"
              placeholder="All"
              class="w-32"
              @change="filterItems"
            />
          </div>
        </div>
      </div>

      <!-- Portfolio Grid -->
      <div v-if="!loading && filteredItems.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="item in filteredItems" 
          :key="item.id"
          class="bg-white rounded-lg border border-gray-200 hover:border-blue-300 transition-colors group"
        >
          <!-- Media Preview -->
          <div class="relative h-48 bg-gray-100 rounded-t-lg overflow-hidden">
            <!-- Image -->
            <img 
              v-if="item.type === 'image' && item.media_url"
              :src="item.media_url"
              :alt="item.title"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            />
            
            <!-- Video Thumbnail -->
            <div 
              v-else-if="item.type === 'video'"
              class="w-full h-full bg-gray-900 flex items-center justify-center relative cursor-pointer"
              @click="playVideo(item)"
            >
              <i class="pi pi-play text-white text-4xl"></i>
              <div class="absolute inset-0 bg-black bg-opacity-30 flex items-center justify-center">
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                  <i class="pi pi-play text-white text-2xl"></i>
                </div>
              </div>
            </div>
            
            <!-- Document/Project Icon -->
            <div 
              v-else
              class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100"
            >
              <div class="text-center">
                <i :class="getTypeIcon(item.type)" class="text-4xl text-blue-600 mb-2"></i>
                <div class="text-sm font-medium text-blue-800 capitalize">{{ item.type }}</div>
              </div>
            </div>

            <!-- Badges -->
            <div class="absolute top-2 left-2 flex gap-1">
              <Badge v-if="item.is_featured" value="Featured" severity="warning" class="text-xs" />
              <Badge v-if="!item.is_public" value="Private" severity="secondary" class="text-xs" />
            </div>

            <!-- Actions -->
            <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
              <div class="flex gap-1">
                <Button 
                  icon="pi pi-eye" 
                  size="small" 
                  rounded 
                  text 
                  severity="secondary"
                  class="bg-white bg-opacity-80 hover:bg-opacity-100"
                  @click="viewItem(item)"
                />
                <Button 
                  icon="pi pi-pencil" 
                  size="small" 
                  rounded 
                  text 
                  severity="secondary"
                  class="bg-white bg-opacity-80 hover:bg-opacity-100"
                  @click="editItem(item)"
                />
                <Button 
                  icon="pi pi-trash" 
                  size="small" 
                  rounded 
                  text 
                  severity="danger"
                  class="bg-white bg-opacity-80 hover:bg-opacity-100"
                  @click="deleteItem(item)"
                />
              </div>
            </div>
          </div>

          <!-- Content -->
          <div class="p-4">
            <div class="flex items-start justify-between mb-2">
              <h3 class="font-semibold text-gray-900 line-clamp-1">{{ item.title }}</h3>
              <Badge :value="item.type" :severity="getTypeSeverity(item.type)" class="text-xs ml-2" />
            </div>
            
            <p v-if="item.description" class="text-sm text-gray-600 line-clamp-2 mb-3">
              {{ item.description }}
            </p>

            <!-- Tags -->
            <div v-if="item.tags && item.tags.length" class="flex flex-wrap gap-1 mb-3">
              <span 
                v-for="tag in item.tags.slice(0, 3)" 
                :key="tag"
                class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full"
              >
                {{ tag }}
              </span>
              <span v-if="item.tags.length > 3" class="text-xs text-gray-500">
                +{{ item.tags.length - 3 }} more
              </span>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between text-xs text-gray-500">
              <div class="flex items-center gap-3">
                <span v-if="item.category">{{ item.category }}</span>
                <span v-if="item.completed_at">{{ formatDate(item.completed_at) }}</span>
              </div>
              <div class="flex items-center gap-1">
                <i class="pi pi-eye"></i>
                <span>{{ item.views || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!loading && filteredItems.length === 0" class="text-center py-12">
        <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
          <i class="pi pi-folder text-gray-400 text-3xl"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No portfolio items found</h3>
        <p class="text-gray-600 mb-4">
          {{ portfolioItems.length === 0 ? 'Start building your portfolio by adding your first item.' : 'Try adjusting your filters to see more items.' }}
        </p>
        <Button 
          label="Add Your First Item" 
          icon="pi pi-plus" 
          @click="showAddModal = true"
        />
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-12">
        <ProgressSpinner size="large" />
      </div>

      <!-- Add/Edit Modal -->
      <Dialog 
        v-model:visible="showAddModal" 
        modal 
        :style="{ width: '90vw', maxWidth: '600px' }"
        :header="editingItem ? 'Edit Portfolio Item' : 'Add Portfolio Item'"
      >
        <form @submit.prevent="saveItem" class="space-y-4">
          <!-- Title -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
            <InputText 
              v-model="form.title" 
              placeholder="Enter item title"
              class="w-full"
              :class="{ 'p-invalid': errors.title }"
            />
            <small v-if="errors.title" class="p-error">{{ errors.title }}</small>
          </div>

          <!-- Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
            <Dropdown 
              v-model="form.type" 
              :options="typeOptions" 
              optionLabel="label"
              optionValue="value"
              placeholder="Select type"
              class="w-full"
              :class="{ 'p-invalid': errors.type }"
            />
            <small v-if="errors.type" class="p-error">{{ errors.type }}</small>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <Textarea 
              v-model="form.description" 
              placeholder="Describe your work..."
              rows="3"
              class="w-full"
            />
          </div>

          <!-- Media Upload -->
          <div v-if="form.type === 'image' || form.type === 'document'">
            <label class="block text-sm font-medium text-gray-700 mb-1">Upload File</label>
            <FileUpload
              mode="basic"
              :auto="false"
              accept="image/*,application/pdf,.doc,.docx"
              :maxFileSize="10000000"
              @select="onFileSelect"
              chooseLabel="Choose File"
              class="w-full"
            />
          </div>

          <!-- External URL -->
          <div v-if="form.type === 'link' || form.type === 'video'">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ form.type === 'video' ? 'Video URL (YouTube, Vimeo)' : 'External Link' }}
            </label>
            <InputText 
              v-model="form.external_url" 
              placeholder="https://..."
              class="w-full"
            />
          </div>

          <!-- Category -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
            <Dropdown 
              v-model="form.category" 
              :options="categoryOptions" 
              optionLabel="label"
              optionValue="value"
              placeholder="Select category"
              class="w-full"
              editable
            />
          </div>

          <!-- Tags -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
            <Chips 
              v-model="form.tags" 
              placeholder="Add tags..."
              class="w-full"
            />
          </div>

          <!-- Completion Date -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Completion Date</label>
            <Calendar 
              v-model="form.completed_at" 
              placeholder="Select date"
              class="w-full"
              dateFormat="yy-mm-dd"
            />
          </div>

          <!-- Settings -->
          <div class="grid grid-cols-2 gap-4">
            <div class="flex items-center gap-2">
              <Checkbox v-model="form.is_public" inputId="is_public" binary />
              <label for="is_public" class="text-sm">Make public</label>
            </div>
            <div class="flex items-center gap-2">
              <Checkbox v-model="form.is_featured" inputId="is_featured" binary />
              <label for="is_featured" class="text-sm">Featured item</label>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-4">
            <Button 
              label="Cancel" 
              outlined 
              @click="closeModal"
            />
            <Button 
              :label="editingItem ? 'Update' : 'Add Item'" 
              type="submit"
              :loading="saving"
            />
          </div>
        </form>
      </Dialog>

      <!-- View Modal -->
      <Dialog 
        v-model:visible="showViewModal" 
        modal 
        :style="{ width: '90vw', maxWidth: '800px' }"
        :header="viewingItem?.title"
      >
        <div v-if="viewingItem" class="space-y-4">
          <!-- Media Display -->
          <div class="bg-gray-100 rounded-lg overflow-hidden">
            <!-- Image -->
            <img 
              v-if="viewingItem.type === 'image' && viewingItem.media_url"
              :src="viewingItem.media_url"
              :alt="viewingItem.title"
              class="w-full max-h-96 object-contain"
            />
            
            <!-- Video Embed -->
            <div 
              v-else-if="viewingItem.type === 'video' && viewingItem.embed_url"
              class="aspect-video"
            >
              <iframe 
                :src="getEmbedUrl(viewingItem.embed_url)"
                class="w-full h-full"
                frameborder="0"
                allowfullscreen
              ></iframe>
            </div>
            
            <!-- External Link -->
            <div 
              v-else-if="viewingItem.type === 'link'"
              class="p-6 text-center"
            >
              <i class="pi pi-external-link text-4xl text-blue-600 mb-4"></i>
              <div class="text-lg font-medium mb-2">External Link</div>
              <Button 
                :label="viewingItem.external_url" 
                icon="pi pi-external-link"
                text
                @click="openExternalLink(viewingItem.external_url)"
              />
            </div>
          </div>

          <!-- Details -->
          <div class="space-y-3">
            <div v-if="viewingItem.description">
              <h4 class="font-medium text-gray-900">Description</h4>
              <p class="text-gray-600">{{ viewingItem.description }}</p>
            </div>

            <div v-if="viewingItem.tags && viewingItem.tags.length" class="flex flex-wrap gap-2">
              <span 
                v-for="tag in viewingItem.tags" 
                :key="tag"
                class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full"
              >
                {{ tag }}
              </span>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm">
              <div v-if="viewingItem.category">
                <span class="font-medium">Category:</span> {{ viewingItem.category }}
              </div>
              <div v-if="viewingItem.completed_at">
                <span class="font-medium">Completed:</span> {{ formatDate(viewingItem.completed_at) }}
              </div>
              <div>
                <span class="font-medium">Views:</span> {{ viewingItem.views || 0 }}
              </div>
              <div>
                <span class="font-medium">Status:</span> 
                <Badge :value="viewingItem.is_public ? 'Public' : 'Private'" :severity="viewingItem.is_public ? 'success' : 'secondary'" />
              </div>
            </div>
          </div>
        </div>
      </Dialog>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import api from '@/lib/api';
import AppLayout from '@/Components/AppLayout.vue';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import Dropdown from 'primevue/dropdown';
import Dialog from 'primevue/dialog';
import ProgressSpinner from 'primevue/progressspinner';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import FileUpload from 'primevue/fileupload';
import Chips from 'primevue/chips';
import Calendar from 'primevue/calendar';
import Checkbox from 'primevue/checkbox';

const toast = useToast();
const confirm = useConfirm();

// State
const loading = ref(true);
const saving = ref(false);
const portfolioItems = ref([]);
const showAddModal = ref(false);
const showViewModal = ref(false);
const editingItem = ref(null);
const viewingItem = ref(null);
const selectedFile = ref(null);

// Form
const form = ref({
  title: '',
  description: '',
  type: '',
  media_url: '',
  external_url: '',
  category: '',
  tags: [],
  completed_at: null,
  is_public: true,
  is_featured: false
});

const errors = ref({});

// Filters
const filters = ref({
  type: null,
  category: null,
  status: null
});

// Options
const typeOptions = ref([
  { label: 'Image', value: 'image' },
  { label: 'Video', value: 'video' },
  { label: 'External Link', value: 'link' },
  { label: 'Document', value: 'document' },
  { label: 'Project', value: 'project' }
]);

const categoryOptions = ref([
  { label: 'Healthcare Projects', value: 'Healthcare Projects' },
  { label: 'Certifications', value: 'Certifications' },
  { label: 'Research Papers', value: 'Research Papers' },
  { label: 'Case Studies', value: 'Case Studies' },
  { label: 'Training Materials', value: 'Training Materials' },
  { label: 'Awards & Recognition', value: 'Awards & Recognition' },
  { label: 'Professional Development', value: 'Professional Development' },
  { label: 'Clinical Work', value: 'Clinical Work' },
  { label: 'Other', value: 'Other' }
]);

const statusOptions = ref([
  { label: 'All', value: null },
  { label: 'Public', value: 'public' },
  { label: 'Private', value: 'private' },
  { label: 'Featured', value: 'featured' }
]);

// Computed
const filteredItems = computed(() => {
  let items = portfolioItems.value;
  
  if (filters.value.type) {
    items = items.filter(item => item.type === filters.value.type);
  }
  
  if (filters.value.category) {
    items = items.filter(item => item.category === filters.value.category);
  }
  
  if (filters.value.status) {
    if (filters.value.status === 'public') {
      items = items.filter(item => item.is_public);
    } else if (filters.value.status === 'private') {
      items = items.filter(item => !item.is_public);
    } else if (filters.value.status === 'featured') {
      items = items.filter(item => item.is_featured);
    }
  }
  
  return items;
});

const publicItems = computed(() => portfolioItems.value.filter(item => item.is_public));
const featuredItems = computed(() => portfolioItems.value.filter(item => item.is_featured));
const totalViews = computed(() => portfolioItems.value.reduce((sum, item) => sum + (item.views || 0), 0));

// Methods
async function loadPortfolio() {
  try {
    loading.value = true;
    const response = await api.get('/portfolio');
    portfolioItems.value = response.data.data || [];
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load portfolio items',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
}

function filterItems() {
  // Trigger reactivity for computed property
}

function resetForm() {
  form.value = {
    title: '',
    description: '',
    type: '',
    media_url: '',
    external_url: '',
    category: '',
    tags: [],
    completed_at: null,
    is_public: true,
    is_featured: false
  };
  errors.value = {};
  selectedFile.value = null;
  editingItem.value = null;
}

function editItem(item) {
  editingItem.value = item;
  form.value = {
    title: item.title,
    description: item.description || '',
    type: item.type,
    media_url: item.media_url || '',
    external_url: item.external_url || '',
    category: item.category || '',
    tags: item.tags || [],
    completed_at: item.completed_at ? new Date(item.completed_at) : null,
    is_public: item.is_public,
    is_featured: item.is_featured
  };
  showAddModal.value = true;
}

function viewItem(item) {
  viewingItem.value = item;
  showViewModal.value = true;
}

function closeModal() {
  showAddModal.value = false;
  resetForm();
}

function onFileSelect(event) {
  selectedFile.value = event.files[0];
}

async function saveItem() {
  try {
    saving.value = true;
    errors.value = {};

    // Prepare data
    const data = { ...form.value };
    
    // Ensure tags is an array (not null/undefined)
    if (!data.tags || !Array.isArray(data.tags)) {
      data.tags = [];
    }
    
    // Ensure boolean fields are proper booleans
    data.is_public = Boolean(data.is_public);
    data.is_featured = Boolean(data.is_featured);
    
    // Handle completion date
    if (data.completed_at && data.completed_at instanceof Date) {
      data.completed_at = data.completed_at.toISOString().split('T')[0];
    }

    // Remove empty string values but keep false booleans and empty arrays
    Object.keys(data).forEach(key => {
      if (data[key] === null || data[key] === undefined || data[key] === '') {
        delete data[key];
      }
    });

    let response;
    
    // If we have a file, use FormData
    if (selectedFile.value) {
      const formData = new FormData();
      
      // Add all form fields
      Object.keys(data).forEach(key => {
        if (key === 'tags' && Array.isArray(data[key])) {
          // Send tags as JSON string for FormData
          formData.append(key, JSON.stringify(data[key]));
        } else if (typeof data[key] === 'boolean') {
          // Convert boolean to string for FormData
          formData.append(key, data[key] ? '1' : '0');
        } else {
          formData.append(key, data[key]);
        }
      });
      
      // Add file
      formData.append('media', selectedFile.value);
      
      if (editingItem.value) {
        response = await api.put(`/portfolio/${editingItem.value.id}`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });
      } else {
        response = await api.post('/portfolio', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });
      }
    } else {
      // No file, use regular JSON
      if (editingItem.value) {
        response = await api.put(`/portfolio/${editingItem.value.id}`, data);
      } else {
        response = await api.post('/portfolio', data);
      }
    }

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: editingItem.value ? 'Portfolio item updated' : 'Portfolio item added',
      life: 3000
    });

    closeModal();
    await loadPortfolio();
  } catch (error) {
    console.error('Portfolio save error:', error);
    console.error('Error response:', error.response);
    
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {};
      console.error('Validation errors:', errors.value);
      toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: 'Please check the form fields and try again',
        life: 5000
      });
    } else {
      const errorMessage = error.response?.data?.message || error.message || 'Failed to save portfolio item';
      console.error('API Error:', errorMessage);
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: errorMessage,
        life: 3000
      });
    }
  } finally {
    saving.value = false;
  }
}

function deleteItem(item) {
  confirm.require({
    message: `Are you sure you want to delete "${item.title}"?`,
    header: 'Confirm Deletion',
    icon: 'pi pi-exclamation-triangle',
    rejectClass: 'p-button-secondary p-button-outlined',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await api.delete(`/portfolio/${item.id}`);
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Portfolio item deleted',
          life: 3000
        });
        await loadPortfolio();
      } catch (error) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to delete portfolio item',
          life: 3000
        });
      }
    }
  });
}

function previewPublic() {
  // Get current user ID and open public portfolio
  const userId = JSON.parse(localStorage.getItem('auth_user'))?.id;
  if (userId) {
    window.open(`/portfolio/${userId}/public`, '_blank');
  }
}

function playVideo(item) {
  viewItem(item);
}

function getTypeIcon(type) {
  const icons = {
    image: 'pi pi-image',
    video: 'pi pi-video',
    link: 'pi pi-external-link',
    document: 'pi pi-file',
    project: 'pi pi-briefcase'
  };
  return icons[type] || 'pi pi-file';
}

function getTypeSeverity(type) {
  const severities = {
    image: 'success',
    video: 'info',
    link: 'warning',
    document: 'secondary',
    project: 'contrast'
  };
  return severities[type] || 'secondary';
}

function getEmbedUrl(url) {
  if (!url) return '';
  
  // YouTube
  if (url.includes('youtube.com') || url.includes('youtu.be')) {
    const videoId = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/)?.[1];
    return videoId ? `https://www.youtube.com/embed/${videoId}` : url;
  }
  
  // Vimeo
  if (url.includes('vimeo.com')) {
    const videoId = url.split('/').pop();
    return `https://player.vimeo.com/video/${videoId}`;
  }
  
  return url;
}

function openExternalLink(url) {
  window.open(url, '_blank');
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
}

// Lifecycle
onMounted(() => {
  loadPortfolio();
});
</script>

<style scoped>
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>