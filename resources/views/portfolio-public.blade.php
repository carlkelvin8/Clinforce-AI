<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="profile">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ request()->url() }}">
    @if($featuredImage)
    <meta property="og:image" content="{{ asset($featuredImage) }}">
    @endif
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    @if($featuredImage)
    <meta name="twitter:image" content="{{ asset($featuredImage) }}">
    @endif
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- PrimeIcons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/primeicons@6.0.1/primeicons.css">
    
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .portfolio-card {
            transition: all 0.3s ease;
        }
        .portfolio-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-sm border-b sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Logo/Brand -->
                <div class="flex items-center space-x-4">
                    <a href="{{ url('/') }}" class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <i class="pi pi-briefcase text-white text-sm"></i>
                        </div>
                        <span class="text-xl font-bold text-gray-900">AI Clinforce</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-6">
                    <a href="{{ url('/') }}" class="text-gray-600 hover:text-blue-600 transition-colors">
                        <i class="pi pi-home mr-2"></i>Home
                    </a>
                    <a href="{{ url('/#jobs') }}" class="text-gray-600 hover:text-blue-600 transition-colors">
                        <i class="pi pi-briefcase mr-2"></i>Jobs
                    </a>
                    <a href="{{ url('/login') }}" class="text-gray-600 hover:text-blue-600 transition-colors">
                        <i class="pi pi-sign-in mr-2"></i>Login
                    </a>
                    <a href="{{ url('/register') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="pi pi-user-plus mr-2"></i>Sign Up
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button id="mobile-menu-btn" class="text-gray-600 hover:text-blue-600">
                        <i class="pi pi-bars text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div id="mobile-menu" class="hidden md:hidden pb-4">
                <div class="flex flex-col space-y-2">
                    <a href="{{ url('/') }}" class="text-gray-600 hover:text-blue-600 transition-colors py-2">
                        <i class="pi pi-home mr-2"></i>Home
                    </a>
                    <a href="{{ url('/#jobs') }}" class="text-gray-600 hover:text-blue-600 transition-colors py-2">
                        <i class="pi pi-briefcase mr-2"></i>Jobs
                    </a>
                    <a href="{{ url('/login') }}" class="text-gray-600 hover:text-blue-600 transition-colors py-2">
                        <i class="pi pi-sign-in mr-2"></i>Login
                    </a>
                    <a href="{{ url('/register') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors inline-block">
                        <i class="pi pi-user-plus mr-2"></i>Sign Up
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="bg-gray-100 border-b">
        <div class="max-w-6xl mx-auto px-4 py-3">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ url('/') }}" class="text-blue-600 hover:text-blue-800">Home</a>
                <i class="pi pi-chevron-right text-gray-400"></i>
                <span class="text-gray-600">Portfolio</span>
                <i class="pi pi-chevron-right text-gray-400"></i>
                <span class="text-gray-900 font-medium">
                    @if($applicantProfile)
                        {{ $applicantProfile->first_name }} {{ $applicantProfile->last_name }}
                    @else
                        {{ $user->name }}
                    @endif
                </span>
            </nav>
        </div>
    </div>
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-6xl mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    @if($applicantProfile && $applicantProfile->avatar)
                    <img src="{{ asset($applicantProfile->avatar) }}" alt="Profile" class="w-16 h-16 rounded-full object-cover">
                    @else
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                        <span class="text-2xl font-bold text-blue-600">
                            {{ substr($user->name ?? $user->email, 0, 1) }}
                        </span>
                    </div>
                    @endif
                    
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            @if($applicantProfile)
                                {{ $applicantProfile->first_name }} {{ $applicantProfile->last_name }}
                            @else
                                {{ $user->name }}
                            @endif
                        </h1>
                        @if($applicantProfile && $applicantProfile->title)
                        <p class="text-gray-600">{{ $applicantProfile->title }}</p>
                        @endif
                        @if($applicantProfile && $applicantProfile->location)
                        <p class="text-sm text-gray-500 flex items-center mt-1">
                            <i class="pi pi-map-marker mr-1"></i>
                            {{ $applicantProfile->location }}
                        </p>
                        @endif
                    </div>
                </div>
                
                <div class="text-right">
                    <div class="text-sm text-gray-500">Professional Portfolio</div>
                    <div class="text-xs text-gray-400">{{ $portfolioStats['total_items'] }} items • {{ $portfolioStats['total_views'] }} views</div>
                </div>
            </div>
            
            @if($applicantProfile && $applicantProfile->bio)
            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                <p class="text-gray-700">{{ $applicantProfile->bio }}</p>
            </div>
            @endif
        </div>
    </header>

    <!-- Stats -->
    <section class="max-w-6xl mx-auto px-4 py-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $portfolioStats['total_items'] }}</div>
                <div class="text-sm text-gray-600">Portfolio Items</div>
            </div>
            <div class="bg-white rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-green-600">{{ $portfolioStats['featured_items'] }}</div>
                <div class="text-sm text-gray-600">Featured Items</div>
            </div>
            <div class="bg-white rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-purple-600">{{ $portfolioStats['total_views'] }}</div>
                <div class="text-sm text-gray-600">Total Views</div>
            </div>
            <div class="bg-white rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-amber-600">{{ $portfolioStats['categories']->count() }}</div>
                <div class="text-sm text-gray-600">Categories</div>
            </div>
        </div>
    </section>

    <!-- Portfolio Items -->
    <main class="max-w-6xl mx-auto px-4 pb-12">
        @if($portfolioItems->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($portfolioItems as $item)
            <div class="portfolio-card bg-white rounded-lg shadow-sm overflow-hidden">
                <!-- Media Preview -->
                <div class="relative h-48 bg-gray-100">
                    @if($item->type === 'image' && $item->media_url)
                    <img src="{{ asset($item->media_url) }}" alt="{{ $item->title }}" class="w-full h-full object-cover">
                    @elseif($item->type === 'video')
                    <div class="w-full h-full bg-gray-900 flex items-center justify-center">
                        <i class="pi pi-play text-white text-4xl"></i>
                    </div>
                    @else
                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100">
                        <div class="text-center">
                            @php
                            $icons = [
                                'image' => 'pi-image',
                                'video' => 'pi-video', 
                                'link' => 'pi-external-link',
                                'document' => 'pi-file',
                                'project' => 'pi-briefcase'
                            ];
                            @endphp
                            <i class="pi {{ $icons[$item->type] ?? 'pi-file' }} text-4xl text-blue-600 mb-2"></i>
                            <div class="text-sm font-medium text-blue-800 capitalize">{{ $item->type }}</div>
                        </div>
                    </div>
                    @endif

                    <!-- Badges -->
                    <div class="absolute top-2 left-2 flex gap-1">
                        @if($item->is_featured)
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full font-medium">Featured</span>
                        @endif
                    </div>

                    <!-- View Count -->
                    <div class="absolute bottom-2 right-2 bg-black bg-opacity-50 text-white text-xs px-2 py-1 rounded">
                        <i class="pi pi-eye mr-1"></i>{{ $item->views }}
                    </div>
                </div>

                <!-- Content -->
                <div class="p-4">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="font-semibold text-gray-900 line-clamp-2">{{ $item->title }}</h3>
                        @php
                        $typeColors = [
                            'image' => 'bg-green-100 text-green-800',
                            'video' => 'bg-blue-100 text-blue-800',
                            'link' => 'bg-yellow-100 text-yellow-800',
                            'document' => 'bg-gray-100 text-gray-800',
                            'project' => 'bg-purple-100 text-purple-800'
                        ];
                        @endphp
                        <span class="px-2 py-1 {{ $typeColors[$item->type] ?? 'bg-gray-100 text-gray-800' }} text-xs rounded-full font-medium capitalize ml-2">
                            {{ $item->type }}
                        </span>
                    </div>
                    
                    @if($item->description)
                    <p class="text-sm text-gray-600 line-clamp-3 mb-3">{{ $item->description }}</p>
                    @endif

                    <!-- Tags -->
                    @if($item->tags && count($item->tags) > 0)
                    <div class="flex flex-wrap gap-1 mb-3">
                        @foreach(array_slice($item->tags, 0, 3) as $tag)
                        <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">{{ $tag }}</span>
                        @endforeach
                        @if(count($item->tags) > 3)
                        <span class="text-xs text-gray-500">+{{ count($item->tags) - 3 }} more</span>
                        @endif
                    </div>
                    @endif

                    <!-- Footer -->
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <div class="flex items-center gap-3">
                            @if($item->category)
                            <span>{{ $item->category }}</span>
                            @endif
                            @if($item->completed_at)
                            <span>{{ \Carbon\Carbon::parse($item->completed_at)->format('M Y') }}</span>
                            @endif
                        </div>
                        
                        @if($item->external_url)
                        <a href="{{ $item->external_url }}" target="_blank" rel="noopener" class="text-blue-600 hover:text-blue-800 flex items-center">
                            <i class="pi pi-external-link mr-1"></i>
                            <span>View</span>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-12">
            <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="pi pi-folder text-gray-400 text-3xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No public portfolio items</h3>
            <p class="text-gray-600">This user hasn't shared any portfolio items publicly yet.</p>
        </div>
        @endif
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t mt-12">
        <div class="max-w-6xl mx-auto px-4 py-6 text-center">
            <p class="text-sm text-gray-500">
                Powered by <a href="{{ url('/') }}" class="text-blue-600 hover:text-blue-800">AI Clinforce Partners</a>
            </p>
        </div>
    </footer>

    <!-- Mobile Menu JavaScript -->
    <script>
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });
    </script>

    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</body>
</html>