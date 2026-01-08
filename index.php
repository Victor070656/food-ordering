<?php
/**
 * Public Landing Page
 * Online Food Ordering - Elegant Design
 */

require_once dirname(__FILE__) . '/config/config.php';

$page_title = 'Home';
$menuItem = new MenuItem();
$paymentSettings = new PaymentSettings();

$featuredItems = $menuItem->getFeatured(8);
$categories = $menuItem->getCategories();
$allItems = $menuItem->getAll(['available' => true]);

// Get payment method details for checkout
$bankTransferDetails = $paymentSettings->getPaymentMethodDetails('bank_transfer');
$posDetails = $paymentSettings->getPaymentMethodDetails('pos');
$bankTransferEnabled = $bankTransferDetails['enabled'] ?? false;
$posEnabled = $posDetails['enabled'] ?? false;

// Group items by category
$itemsByCategory = [];
foreach ($allItems as $item) {
    $itemsByCategory[$item['category']][] = $item;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Authentic Nigerian Delivered</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        amber: {
                            50: '#fefce8',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                        },
                        orange: {
                            400: '#fb923c',
                            500: '#f97316',
                            600: '#ea580c',
                        }
                    },
                    fontFamily: {
                        playfair: ['"Playfair Display"', 'serif'],
                        inter: ['"Inter"', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-playfair { font-family: 'Playfair Display', serif; }
        .hero-gradient {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.95) 0%, rgba(234, 88, 12, 0.9) 100%);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(245, 158, 11, 0.3); }
            50% { box-shadow: 0 0 40px rgba(245, 158, 11, 0.6); }
        }
        .image-shine {
            position: relative;
            overflow: hidden;
        }
        .image-shine::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(to right, transparent 0%, rgba(255,255,255,0.3) 50%, transparent 100%);
            transform: rotate(45deg);
            transition: all 0.6s;
        }
        .image-shine:hover::after {
            left: 100%;
        }
    </style>
</head>
<body class="bg-stone-50">

<!-- Navigation -->
<nav class="bg-white/95 backdrop-blur-md shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
            <a href="<?php echo SITE_URL; ?>/" class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-utensils text-white text-lg"></i>
                </div>
                <div>
                    <span class="font-playfair font-bold text-2xl text-gray-800"><?php echo SITE_NAME; ?></span>
                    <p class="text-xs text-gray-400 -mt-1">Nigerian Kitchen</p>
                </div>
            </a>
            <div class="hidden md:flex items-center gap-8">
                <a href="#featured" class="text-gray-600 hover:text-amber-600 font-medium transition-colors">Featured</a>
                <a href="#menu" class="text-gray-600 hover:text-amber-600 font-medium transition-colors">Menu</a>
                <a href="#about" class="text-gray-600 hover:text-amber-600 font-medium transition-colors">About</a>
                <a href="#contact" class="text-gray-600 hover:text-amber-600 font-medium transition-colors">Contact</a>
            </div>
            <div class="flex items-center gap-4">
                <?php if (isLoggedIn() && hasRole('customer')): ?>
                    <a href="dashboard.php" class="hidden sm:flex items-center gap-2 text-gray-600 hover:text-amber-600 font-medium">
                        <i class="fas fa-receipt"></i>
                        <span>My Orders</span>
                    </a>
                    <a href="profile.php" class="hidden sm:flex items-center gap-2 text-gray-600 hover:text-amber-600 font-medium">
                        <i class="fas fa-user"></i>
                    </a>
                    <a href="logout.php" class="text-red-500 hover:text-red-600 font-medium">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                <?php else: ?>
                    <a href="auth.php" class="hidden sm:block px-5 py-2.5 text-amber-600 border-2 border-amber-600 rounded-xl font-semibold hover:bg-amber-50 transition-colors">
                        Sign In
                    </a>
                    <a href="auth.php" class="sm:hidden w-10 h-10 flex items-center justify-center bg-amber-500 text-white rounded-xl">
                        <i class="fas fa-user"></i>
                    </a>
                <?php endif; ?>
                <button onclick="openCart()" class="relative p-3 text-gray-600 hover:text-amber-600 transition-colors">
                    <i class="fas fa-shopping-bag text-xl"></i>
                    <span id="cartCount" class="absolute -top-1 -right-1 w-6 h-6 bg-gradient-to-r from-amber-500 to-orange-600 text-white text-xs rounded-full flex items-center justify-center hidden font-bold">0</span>
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="relative min-h-[90vh] flex items-center overflow-hidden">
    <!-- Background Image -->
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1643059687317-2ee7c701c885?w=1920&q=80');">
        <div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/50 to-black/70"></div>
    </div>

    <!-- Floating Food Images -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-20 right-10 w-32 h-32 md:w-48 md:h-48 rounded-full overflow-hidden floating opacity-80 shadow-2xl">
            <img src="https://images.unsplash.com/photo-1512058564366-18510be2db19?w=300&q=80" alt="Jollof Rice" class="w-full h-full object-cover">
        </div>
        <div class="absolute bottom-32 right-1/4 w-24 h-24 md:w-36 md:h-36 rounded-full overflow-hidden floating opacity-70 shadow-2xl" style="animation-delay: 1s;">
            <img src="https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=300&q=80" alt="Grilled Chicken" class="w-full h-full object-cover">
        </div>
        <div class="absolute bottom-40 left-10 w-28 h-28 md:w-40 md:h-40 rounded-full overflow-hidden floating opacity-60 shadow-2xl" style="animation-delay: 2s;">
            <img src="https://images.unsplash.com/photo-1601050690597-df0568f70950?w=300&q=80" alt="Fried Plantain" class="w-full h-full object-cover">
        </div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="max-w-2xl">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500/20 backdrop-blur rounded-full mb-6">
                <span class="w-2 h-2 bg-amber-400 rounded-full animate-pulse"></span>
                <span class="text-amber-300 font-medium">Now delivering in your area</span>
            </div>
            <h1 class="font-playfair text-5xl md:text-7xl font-bold text-white mb-6 leading-tight">
                Taste the <span class="text-amber-400">Heart</span> of Nigeria
            </h1>
            <p class="text-xl text-gray-300 mb-8 leading-relaxed">
                Experience authentic Nigerian flavors prepared with love. From sizzling Jollof Rice to rich Egusi Soup, we bring home-cooked goodness to your doorstep.
            </p>
            <div class="flex flex-wrap gap-4">
                <a href="#menu" class="group px-8 py-4 bg-gradient-to-r from-amber-500 to-orange-600 text-white font-semibold rounded-2xl hover:shadow-2xl hover:shadow-amber-500/30 transition-all flex items-center gap-3">
                    <i class="fas fa-utensils"></i>
                    <span>Explore Menu</span>
                    <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </a>
                <a href="tel:+2348000000000" class="px-8 py-4 bg-white/10 backdrop-blur text-white font-semibold rounded-2xl border border-white/30 hover:bg-white/20 transition-all flex items-center gap-3">
                    <i class="fas fa-phone"></i>
                    <span>Call to Order</span>
                </a>
            </div>

            <!-- Stats -->
            <div class="flex flex-wrap gap-8 mt-12 pt-8 border-t border-white/20">
                <div>
                    <p class="text-3xl font-bold text-white">5000+</p>
                    <p class="text-gray-400">Happy Customers</p>
                </div>
                <div>
                    <p class="text-3xl font-bold text-white">50+</p>
                    <p class="text-gray-400">Menu Items</p>
                </div>
                <div>
                    <p class="text-3xl font-bold text-white">4.9</p>
                    <p class="text-gray-400">Customer Rating</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Strip -->
<section class="bg-gradient-to-r from-amber-500 to-orange-600 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div class="flex items-center justify-center gap-3 text-white">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-truck text-xl"></i>
                </div>
                <div>
                    <p class="font-semibold">Fast Delivery</p>
                    <p class="text-xs text-white/70">30-45 mins</p>
                </div>
            </div>
            <div class="flex items-center justify-center gap-3 text-white">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-leaf text-xl"></i>
                </div>
                <div>
                    <p class="font-semibold">Fresh Ingredients</p>
                    <p class="text-xs text-white/70">Daily sourced</p>
                </div>
            </div>
            <div class="flex items-center justify-center gap-3 text-white">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-heart text-xl"></i>
                </div>
                <div>
                    <p class="font-semibold">Made with Love</p>
                    <p class="text-xs text-white/70">Home-cooked</p>
                </div>
            </div>
            <div class="flex items-center justify-center gap-3 text-white">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-shield-alt text-xl"></i>
                </div>
                <div>
                    <p class="font-semibold">Safe Payment</p>
                    <p class="text-xs text-white/70">Pay on delivery</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Dishes -->
<?php if (!empty($featuredItems)): ?>
<section id="featured" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <span class="text-amber-600 font-medium tracking-wider uppercase text-sm">Our Best Sellers</span>
            <h2 class="font-playfair text-4xl md:text-5xl font-bold text-gray-800 mt-2">Featured Dishes</h2>
            <p class="text-gray-500 mt-4 max-w-2xl mx-auto">Discover our most loved meals, prepared with authentic recipes and fresh ingredients</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php
            $defaultFoodImages = [
                'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=400&q=80',
                'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&q=80',
                'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400&q=80',
                'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=80',
            ];
            foreach ($featuredItems as $index => $item):
            $imgUrl = !empty($item['image_url']) ? $item['image_url'] : $defaultFoodImages[$index % count($defaultFoodImages)];
            ?>
            <div class="group">
                <div class="relative rounded-3xl overflow-hidden shadow-lg card-hover image-shine">
                    <div class="aspect-[4/3] overflow-hidden">
                        <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    </div>
                    <div class="absolute top-4 right-4">
                        <?php if ($item['spice_level'] !== 'none'): ?>
                        <span class="px-3 py-1 bg-white/90 backdrop-blur rounded-full text-sm flex items-center gap-1" title="<?php echo ucfirst($item['spice_level']); ?>">
                            <?php echo $menuItem->getSpiceLabel($item['spice_level'])['icon']; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                    <div class="absolute bottom-0 left-0 right-0 p-5">
                        <h3 class="text-white font-semibold text-lg mb-1"><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p class="text-gray-300 text-sm line-clamp-2 mb-3"><?php echo htmlspecialchars($item['description'] ?? 'Delicious homemade meal'); ?></p>
                        <div class="flex items-center justify-between">
                            <span class="text-2xl font-bold text-amber-400"><?php echo formatCurrency($item['price']); ?></span>
                            <button onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>', <?php echo $item['price']; ?>)" class="w-12 h-12 bg-gradient-to-r from-amber-500 to-orange-600 rounded-xl flex items-center justify-center text-white hover:shadow-lg transition-all">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Full Menu Section -->
<section id="menu" class="py-20 bg-gradient-to-b from-stone-100 to-stone-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <span class="text-amber-600 font-medium tracking-wider uppercase text-sm">Complete Menu</span>
            <h2 class="font-playfair text-4xl md:text-5xl font-bold text-gray-800 mt-2">Our Kitchen</h2>
            <p class="text-gray-500 mt-4">Explore all our delicious Nigerian dishes</p>
        </div>

        <!-- Category Tabs -->
        <div class="flex flex-wrap justify-center gap-3 mb-12">
            <button onclick="filterCategory('all')" class="category-tab active px-6 py-3 rounded-full text-sm font-medium bg-gradient-to-r from-amber-500 to-orange-600 text-white shadow-lg transition-all" data-category="all">
                <i class="fas fa-th-large mr-2"></i>All Items
            </button>
            <?php
            $categoryIcons = [
                'Rice' => 'fa-bowl-food',
                'Soups' => 'fa-mug-hot',
                'Chicken' => 'fa-drumstick-bite',
                'Grills' => 'fa-fire',
                'Beans' => 'fa-seedling',
                'Yam' => 'fa-carrot',
                'Sides' => 'fa-bread-slice',
                'Drinks' => 'fa-glass-water',
                'Breakfast' => 'fa-sun',
                'Pasta' => 'fa-utensils',
                'Wraps' => 'fa-burrito',
            ];
            foreach ($categories as $cat):
            $icon = $categoryIcons[$cat] ?? 'fa-utensils';
            ?>
            <button onclick="filterCategory('<?php echo htmlspecialchars($cat); ?>')" class="category-tab px-6 py-3 rounded-full text-sm font-medium bg-white text-gray-600 shadow-sm hover:shadow-md transition-all border border-gray-200" data-category="<?php echo htmlspecialchars($cat); ?>">
                <i class="fas <?php echo $icon; ?> mr-2"></i><?php echo htmlspecialchars($cat); ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Menu Items Grid -->
        <div id="menuGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php
            $defaultMenuImages = [
                'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=300&q=80',
                'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=300&q=80',
                'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=300&q=80',
                'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=300&q=80',
                'https://images.unsplash.com/photo-1626804475297-41608ea09aeb?w=300&q=80',
                'https://images.unsplash.com/photo-1585937422122-7c299524d14e?w=300&q=80',
            ];
            foreach ($allItems as $index => $item):
            $imgUrl = !empty($item['image_url']) ? $item['image_url'] : $defaultMenuImages[$index % count($defaultMenuImages)];
            ?>
            <div class="menu-item group bg-white rounded-3xl overflow-hidden shadow-lg hover:shadow-2xl transition-all" data-category="<?php echo htmlspecialchars($item['category']); ?>">
                <div class="relative overflow-hidden">
                    <div class="aspect-[4/3] overflow-hidden">
                        <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    </div>
                    <div class="absolute top-4 left-4">
                        <span class="px-3 py-1 bg-amber-500 text-white text-xs font-semibold rounded-full shadow">
                            <?php echo htmlspecialchars($item['category']); ?>
                        </span>
                    </div>
                    <div class="absolute top-4 right-4 flex gap-2">
                        <?php if ($item['spice_level'] !== 'none'): ?>
                        <span class="px-3 py-1 bg-white/90 backdrop-blur rounded-full text-sm" title="<?php echo ucfirst($item['spice_level']); ?>">
                            <?php echo $menuItem->getSpiceLabel($item['spice_level'])['icon']; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="p-5">
                    <h3 class="font-semibold text-gray-800 text-lg mb-2"><?php echo htmlspecialchars($item['name']); ?></h3>
                    <p class="text-gray-500 text-sm mb-4 line-clamp-2"><?php echo htmlspecialchars($item['description'] ?? 'Tasty homemade meal'); ?></p>
                    <div class="flex items-center justify-between">
                        <span class="text-2xl font-bold text-amber-600"><?php echo formatCurrency($item['price']); ?></span>
                        <button onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>', <?php echo $item['price']; ?>)" class="w-12 h-12 bg-gradient-to-r from-amber-500 to-orange-600 rounded-xl flex items-center justify-center text-white hover:shadow-lg transition-all">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- About Section -->
<section id="about" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div class="relative">
                <div class="relative">
                    <img src="https://images.unsplash.com/photo-1556910103-1c02745aae4d?w=600&q=80" alt="Our Kitchen" class="rounded-3xl shadow-2xl">
                    <div class="absolute -bottom-8 -right-8 w-48 h-48 bg-gradient-to-br from-amber-500 to-orange-600 rounded-3xl -z-10"></div>
                    <div class="absolute -top-8 -left-8 w-32 h-32 bg-amber-200 rounded-full -z-10"></div>
                </div>
            </div>
            <div>
                <span class="text-amber-600 font-medium tracking-wider uppercase text-sm">Our Story</span>
                <h2 class="font-playfair text-4xl md:text-5xl font-bold text-gray-800 mt-2 mb-6">Made with Love, Served with Passion</h2>
                <p class="text-gray-600 text-lg mb-6 leading-relaxed">
                    We believe that food is not just about sustenance—it's about culture, community, and connection. Our dishes are prepared using time-honored recipes passed down through generations.
                </p>
                <p class="text-gray-600 mb-8 leading-relaxed">
                    Every meal is crafted with fresh, locally-sourced ingredients and cooked with the same love and care as a home-cooked meal. We bring the authentic taste of Nigeria to your table.
                </p>
                <div class="grid grid-cols-2 gap-6">
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 bg-amber-100 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-award text-amber-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">Authentic Recipes</p>
                            <p class="text-sm text-gray-500">Traditional taste</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 bg-green-100 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-seedling text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">Fresh Daily</p>
                            <p class="text-sm text-gray-500">Quality ingredients</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="py-20 bg-gradient-to-br from-amber-500 to-orange-600">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <span class="text-amber-200 font-medium tracking-wider uppercase text-sm">Testimonials</span>
            <h2 class="font-playfair text-4xl md:text-5xl font-bold text-white mt-2">What Our Customers Say</h2>
        </div>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white/10 backdrop-blur rounded-3xl p-8 border border-white/20">
                <div class="flex items-center gap-1 mb-4">
                    <i class="fas fa-star text-amber-300"></i>
                    <i class="fas fa-star text-amber-300"></i>
                    <i class="fas fa-star text-amber-300"></i>
                    <i class="fas fa-star text-amber-300"></i>
                    <i class="fas fa-star text-amber-300"></i>
                </div>
                <p class="text-white/90 mb-6">"The Jollof rice tastes exactly like my grandmother's recipe. The flavors are authentic and the delivery was super fast!"</p>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-white"></i>
                    </div>
                    <div>
                        <p class="text-white font-semibold">Adaeze Okafor</p>
                        <p class="text-amber-200 text-sm">Loyal Customer</p>
                    </div>
                </div>
            </div>
            <div class="bg-white/10 backdrop-blur rounded-3xl p-8 border border-white/20">
                <div class="flex items-center gap-1 mb-4">
                    <i class="fas fa-star text-amber-300"></i>
                    <i class="fas fa-star text-amber-300"></i>
                    <i class="fas fa-star text-amber-300"></i>
                    <i class="fas fa-star text-amber-300"></i>
                    <i class="fas fa-star text-amber-300"></i>
                </div>
                <p class="text-white/90 mb-6">"Best Suya in town! The meat is perfectly spiced and always tender. This has become my go-to for weekend cravings."</p>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-white"></i>
                    </div>
                    <div>
                        <p class="text-white font-semibold">Emeka Nnamdi</p>
                        <p class="text-amber-200 text-sm">Food Blogger</p>
                    </div>
                </div>
            </div>
            <div class="bg-white/10 backdrop-blur rounded-3xl p-8 border border-white/20">
                <div class="flex items-center gap-1 mb-4">
                    <i class="fas fa-star text-amber-300"></i>
                    <i class="fas fa-star text-amber-300"></i>
                    <i class="fas fa-star text-amber-300"></i>
                    <i class="fas fa-star text-amber-300"></i>
                    <i class="fas fa-star text-amber-300"></i>
                </div>
                <p class="text-white/90 mb-6">"The Egusi soup is divine! Reminds me of Sunday afternoons at home. Great portion sizes and fair prices too."</p>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-white"></i>
                    </div>
                    <div>
                        <p class="text-white font-semibold">Ngozi Adebayo</p>
                        <p class="text-amber-200 text-sm">Regular Customer</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact/CTA Section -->
<section id="contact" class="py-20 bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gradient-to-r from-amber-500 to-orange-600 rounded-3xl p-12 text-center relative overflow-hidden">
            <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1200&q=80')] opacity-10 bg-cover bg-center"></div>
            <div class="relative">
                <h2 class="font-playfair text-4xl md:text-5xl font-bold text-white mb-4">Hungry yet?</h2>
                <p class="text-xl text-white/90 mb-8 max-w-2xl mx-auto">Order now and experience the authentic taste of Nigerian cuisine delivered to your doorstep.</p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="#menu" class="px-10 py-4 bg-white text-amber-600 font-bold rounded-2xl hover:bg-gray-100 transition-colors shadow-xl">
                        <i class="fas fa-utensils mr-2"></i>Order Now
                    </a>
                    <a href="tel:+2348000000000" class="px-10 py-4 bg-transparent border-2 border-white text-white font-bold rounded-2xl hover:bg-white/10 transition-colors">
                        <i class="fas fa-phone mr-2"></i>Call Us
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-gray-900 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-4 gap-12 mb-12">
            <div class="md:col-span-2">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-utensils text-white"></i>
                    </div>
                    <div>
                        <span class="font-playfair font-bold text-2xl"><?php echo SITE_NAME; ?></span>
                        <p class="text-gray-400 text-sm">Nigerian Kitchen</p>
                    </div>
                </div>
                <p class="text-gray-400 mb-6 max-w-md">Delivering authentic Nigerian flavors made with love. We bring home-cooked goodness to your doorstep, one meal at a time.</p>
                <div class="flex gap-4">
                    <a href="#" class="w-10 h-10 bg-gray-800 rounded-xl flex items-center justify-center hover:bg-amber-500 transition-colors">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-gray-800 rounded-xl flex items-center justify-center hover:bg-amber-500 transition-colors">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-gray-800 rounded-xl flex items-center justify-center hover:bg-amber-500 transition-colors">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-gray-800 rounded-xl flex items-center justify-center hover:bg-amber-500 transition-colors">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>
            <div>
                <h4 class="font-semibold mb-6 text-lg">Quick Links</h4>
                <ul class="space-y-3 text-gray-400">
                    <li><a href="#featured" class="hover:text-amber-400 transition-colors">Featured Dishes</a></li>
                    <li><a href="#menu" class="hover:text-amber-400 transition-colors">Full Menu</a></li>
                    <li><a href="#about" class="hover:text-amber-400 transition-colors">About Us</a></li>
                    <li><a href="#contact" class="hover:text-amber-400 transition-colors">Contact</a></li>
                    <?php if (isLoggedIn() && hasRole('customer')): ?>
                    <li><a href="dashboard.php" class="hover:text-amber-400 transition-colors">My Orders</a></li>
                    <?php else: ?>
                    <li><a href="auth.php" class="hover:text-amber-400 transition-colors">Sign In / Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold mb-6 text-lg">Contact Us</h4>
                <ul class="space-y-4 text-gray-400">
                    <li class="flex items-center gap-3">
                        <i class="fas fa-phone text-amber-500"></i>
                        <span>+234 800 000 0000</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <i class="fas fa-envelope text-amber-500"></i>
                        <span>orders@foodsys.com</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <i class="fas fa-clock text-amber-500"></i>
                        <span>Daily: 9am - 10pm</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <i class="fas fa-map-marker-alt text-amber-500"></i>
                        <span>Lagos, Nigeria</span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-gray-400 text-sm">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            <p class="text-gray-500 text-sm">Made with <i class="fas fa-heart text-red-500"></i> in Nigeria</p>
        </div>
    </div>
</footer>

<!-- Cart Modal -->
<div id="cartModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-md max-h-[90vh] overflow-hidden shadow-2xl">
        <div class="bg-gradient-to-r from-amber-500 to-orange-600 p-6 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-white">Your Cart</h2>
            <button onclick="closeCart()" class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center text-white hover:bg-white/30 transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="cartItems" class="p-6">
            <div id="emptyCart" class="text-center py-12">
                <div class="w-24 h-24 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shopping-basket text-4xl text-amber-500"></i>
                </div>
                <p class="text-gray-400 text-lg">Your cart is empty</p>
                <p class="text-gray-400 text-sm mt-2">Add some delicious meals!</p>
            </div>
            <div id="cartItemsList" class="space-y-4 hidden"></div>
        </div>
        <div id="cartSummary" class="hidden border-t border-gray-100 p-6 bg-gray-50">
            <div class="flex justify-between mb-3">
                <span class="text-gray-600">Subtotal</span>
                <span id="subtotal" class="font-semibold text-gray-800"><?php echo formatCurrency(0); ?></span>
            </div>
            <div class="flex justify-between mb-4">
                <span class="text-gray-600">Delivery Fee</span>
                <span id="deliveryFee" class="font-semibold text-gray-800"><?php echo formatCurrency(DEFAULT_DELIVERY_FEE); ?></span>
            </div>
            <div class="flex justify-between text-xl font-bold pt-4 border-t border-gray-200 mb-6">
                <span class="text-gray-800">Total</span>
                <span id="total" class="text-amber-600"><?php echo formatCurrency(DEFAULT_DELIVERY_FEE); ?></span>
            </div>
            <button onclick="openCheckout()" class="w-full py-4 bg-gradient-to-r from-amber-500 to-orange-600 text-white font-semibold rounded-2xl hover:shadow-lg transition-all">
                <i class="fas fa-arrow-right mr-2"></i>Proceed to Checkout
            </button>
        </div>
    </div>
</div>

<!-- Checkout Modal -->
<div id="checkoutModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="bg-gradient-to-r from-amber-500 to-orange-600 p-6 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-white">Checkout</h2>
            <button onclick="closeCheckout()" class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center text-white hover:bg-white/30 transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="checkoutForm" enctype="multipart/form-data" class="p-6 space-y-5">
            <div class="bg-amber-50 rounded-2xl p-5">
                <p class="text-sm text-amber-700 mb-1">Delivering to</p>
                <p class="font-semibold text-gray-800 text-lg"><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></p>
                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Address *</label>
                <textarea name="address" rows="2" required class="w-full px-4 py-3 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all" placeholder="Enter your full delivery address"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Special Instructions</label>
                <textarea name="instructions" rows="2" class="w-full px-4 py-3 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all" placeholder="Any special requests..."></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method *</label>
                <div class="grid grid-cols-3 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="payment_method" value="cash_on_delivery" checked class="peer hidden" onchange="togglePaymentDetails()">
                        <div class="peer-checked:bg-amber-500 peer-checked:text-white peer-checked:border-amber-500 border-2 border-gray-200 rounded-xl p-3 text-center transition-all">
                            <i class="fas fa-money-bill block mb-1"></i>
                            <span class="text-xs">Cash</span>
                        </div>
                    </label>
                    <label class="cursor-pointer <?php echo !$bankTransferEnabled ? 'hidden' : ''; ?>">
                        <input type="radio" name="payment_method" value="bank_transfer" class="peer hidden" onchange="togglePaymentDetails()">
                        <div class="peer-checked:bg-amber-500 peer-checked:text-white peer-checked:border-amber-500 border-2 border-gray-200 rounded-xl p-3 text-center transition-all">
                            <i class="fas fa-university block mb-1"></i>
                            <span class="text-xs">Transfer</span>
                        </div>
                    </label>
                    <label class="cursor-pointer <?php echo !$posEnabled ? 'hidden' : ''; ?>">
                        <input type="radio" name="payment_method" value="pos" class="peer hidden" onchange="togglePaymentDetails()">
                        <div class="peer-checked:bg-amber-500 peer-checked:text-white peer-checked:border-amber-500 border-2 border-gray-200 rounded-xl p-3 text-center transition-all">
                            <i class="fas fa-credit-card block mb-1"></i>
                            <span class="text-xs">POS</span>
                        </div>
                    </label>
                </div>

                <!-- Bank Transfer Details -->
                <div id="bankTransferDetails" class="hidden mt-4 bg-blue-50 rounded-2xl p-4">
                    <p class="text-xs font-semibold text-blue-600 mb-3">BANK TRANSFER DETAILS</p>
                    <?php if (!empty($bankTransferDetails['bank_name'])): ?>
                    <div class="space-y-3 bg-white rounded-xl p-4">
                        <?php if (!empty($bankTransferDetails['bank_name'])): ?>
                        <div class="flex items-center gap-2 text-sm">
                            <i class="fas fa-building-columns text-blue-500 w-5"></i>
                            <span class="font-medium"><?php echo htmlspecialchars($bankTransferDetails['bank_name']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($bankTransferDetails['account_name'])): ?>
                        <div class="flex items-center gap-2 text-sm">
                            <i class="fas fa-user text-gray-400 w-5"></i>
                            <span><?php echo htmlspecialchars($bankTransferDetails['account_name']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($bankTransferDetails['account_number'])): ?>
                        <div class="flex items-center gap-2 text-sm">
                            <i class="fas fa-hashtag text-gray-400 w-5"></i>
                            <span class="font-mono"><?php echo htmlspecialchars($bankTransferDetails['account_number']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($bankTransferDetails['instructions'])): ?>
                        <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($bankTransferDetails['instructions']); ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- Screenshot Upload -->
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-camera mr-1"></i>Payment Screenshot
                            </label>
                            <div class="relative">
                                <input type="file" name="payment_screenshot" accept="image/*" id="bankScreenshot" class="hidden" onchange="previewScreenshot(this, 'bankPreview')">
                                <label for="bankScreenshot" class="flex items-center justify-center gap-2 w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer hover:border-amber-500 hover:bg-amber-50 transition-all">
                                    <i class="fas fa-cloud-upload-alt text-gray-400"></i>
                                    <span class="text-sm text-gray-500" id="bankUploadText">Upload payment screenshot</span>
                                </label>
                                <div id="bankPreview" class="hidden mt-2 relative">
                                    <img src="" alt="Preview" class="w-full h-32 object-cover rounded-xl">
                                    <button type="button" onclick="clearScreenshot('bankScreenshot', 'bankPreview', 'bankUploadText')" class="absolute top-2 right-2 w-8 h-8 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Upload a screenshot of your transfer confirmation</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <p class="text-sm text-gray-400 italic">Bank transfer not available</p>
                    <?php endif; ?>
                </div>

                <!-- POS Details -->
                <div id="posDetails" class="hidden mt-4 bg-purple-50 rounded-2xl p-4">
                    <p class="text-xs font-semibold text-purple-600 mb-3">PAY VIA POS TERMINAL</p>
                    <?php if (!empty($posDetails['bank_name'])): ?>
                    <div class="bg-white rounded-xl p-4 space-y-3">
                        <?php if (!empty($posDetails['instructions'])): ?>
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-credit-card text-purple-500"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">Pay via POS Terminal</p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($posDetails['instructions']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <p class="text-xs font-semibold text-purple-600 pt-2 border-t border-gray-100">TRANSFER TO:</p>

                        <?php if (!empty($posDetails['bank_name'])): ?>
                        <div class="flex items-center gap-2 text-sm">
                            <i class="fas fa-building-columns text-purple-500 w-5"></i>
                            <span class="font-medium"><?php echo htmlspecialchars($posDetails['bank_name']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($posDetails['account_name'])): ?>
                        <div class="flex items-center gap-2 text-sm">
                            <i class="fas fa-user text-gray-400 w-5"></i>
                            <span><?php echo htmlspecialchars($posDetails['account_name']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($posDetails['account_number'])): ?>
                        <div class="flex items-center gap-2 text-sm">
                            <i class="fas fa-hashtag text-gray-400 w-5"></i>
                            <span class="font-mono"><?php echo htmlspecialchars($posDetails['account_number']); ?></span>
                        </div>
                        <?php endif; ?>

                        <!-- Screenshot Upload -->
                        <div class="mt-4 pt-3 border-t border-gray-100">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-camera mr-1"></i>Upload Payment Receipt *
                            </label>
                            <div class="relative">
                                <input type="file" name="pos_payment_screenshot" accept="image/*" id="posScreenshot" class="hidden" onchange="previewScreenshot(this, 'posPreview')">
                                <label for="posScreenshot" class="flex items-center justify-center gap-2 w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer hover:border-amber-500 hover:bg-amber-50 transition-all">
                                    <i class="fas fa-cloud-upload-alt text-gray-400"></i>
                                    <span class="text-sm text-gray-500" id="posUploadText">Upload receipt screenshot</span>
                                </label>
                                <div id="posPreview" class="hidden mt-2 relative">
                                    <img src="" alt="Preview" class="w-full h-32 object-cover rounded-xl">
                                    <button type="button" onclick="clearScreenshot('posScreenshot', 'posPreview', 'posUploadText')" class="absolute top-2 right-2 w-8 h-8 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Upload a screenshot of your POS terminal receipt</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <p class="text-sm text-gray-400 italic">POS payment not available</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="bg-gradient-to-r from-amber-50 to-orange-50 rounded-2xl p-5">
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Order Total</span>
                    <span id="checkoutTotal" class="font-bold text-2xl text-amber-600"><?php echo formatCurrency(DEFAULT_DELIVERY_FEE); ?></span>
                </div>
                <p class="text-xs text-gray-500">You'll pay on delivery</p>
            </div>
            <button type="submit" class="w-full py-4 bg-gradient-to-r from-amber-500 to-orange-600 text-white font-semibold rounded-2xl hover:shadow-lg transition-all">
                <i class="fas fa-check mr-2"></i>Place Order
            </button>
        </form>
    </div>
</div>

<!-- Order Success Modal -->
<div id="successModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-sm text-center p-10 shadow-2xl">
        <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-check text-5xl text-green-500"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-3">Order Placed!</h2>
        <p class="text-gray-500 mb-4">Your order has been received. You can track its status in your dashboard.</p>
        <p class="bg-amber-50 text-amber-600 font-bold py-3 px-6 rounded-2xl mb-6 inline-block">Order #<span id="orderNumber"></span></p>
        <div class="flex flex-col gap-3">
            <a href="dashboard.php" class="w-full py-4 bg-gradient-to-r from-amber-500 to-orange-600 text-white font-semibold rounded-2xl hover:shadow-lg transition-all">
                <i class="fas fa-receipt mr-2"></i>Track Order
            </a>
            <button onclick="closeSuccess()" class="w-full py-4 border-2 border-gray-200 text-gray-700 font-semibold rounded-2xl hover:bg-gray-50 transition-colors">
                Continue Shopping
            </button>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="hidden fixed bottom-8 right-8 bg-gray-800 text-white px-6 py-4 rounded-2xl shadow-2xl z-50 flex items-center gap-3">
    <i class="fas fa-check-circle text-green-400"></i>
    <span id="toastMessage"></span>
</div>

<script>
// Cart management
let cart = [];

function addToCart(id, name, price) {
    const existingItem = cart.find(item => item.id === id);
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push({ id, name, price, quantity: 1 });
    }
    updateCartUI();
    showToast(`${name} added to cart`);
}

function removeFromCart(id) {
    cart = cart.filter(item => item.id !== id);
    updateCartUI();
}

function updateQuantity(id, change) {
    const item = cart.find(item => item.id === id);
    if (item) {
        item.quantity += change;
        if (item.quantity <= 0) {
            removeFromCart(id);
        } else {
            updateCartUI();
        }
    }
}

function updateCartUI() {
    const cartCount = document.getElementById('cartCount');
    const cartItemsList = document.getElementById('cartItemsList');
    const emptyCart = document.getElementById('emptyCart');
    const cartSummary = document.getElementById('cartSummary');
    const subtotalEl = document.getElementById('subtotal');
    const totalEl = document.getElementById('total');

    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const deliveryFee = <?php echo DEFAULT_DELIVERY_FEE; ?>;
    const total = cart.length > 0 ? subtotal + deliveryFee : 0;

    if (totalItems > 0) {
        cartCount.textContent = totalItems;
        cartCount.classList.remove('hidden');
        emptyCart.classList.add('hidden');
        cartItemsList.classList.remove('hidden');
        cartSummary.classList.remove('hidden');

        cartItemsList.innerHTML = cart.map(item => `
            <div class="flex items-center justify-between bg-white rounded-2xl p-4 shadow-sm">
                <div class="flex-1">
                    <p class="font-semibold text-gray-800">${item.name}</p>
                    <p class="text-amber-600 font-medium"><?php echo DEFAULT_CURRENCY; ?>${item.price.toFixed(2)}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="updateQuantity(${item.id}, -1)" class="w-9 h-9 flex items-center justify-center bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">-</button>
                    <span class="w-8 text-center font-semibold">${item.quantity}</span>
                    <button onclick="updateQuantity(${item.id}, 1)" class="w-9 h-9 flex items-center justify-center bg-amber-500 text-white rounded-xl hover:bg-amber-600 transition-colors">+</button>
                    <button onclick="removeFromCart(${item.id})" class="w-9 h-9 flex items-center justify-center text-red-500 hover:bg-red-50 rounded-xl transition-colors">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');

        subtotalEl.textContent = '<?php echo DEFAULT_CURRENCY; ?>' + subtotal.toFixed(2);
        totalEl.textContent = '<?php echo DEFAULT_CURRENCY; ?>' + total.toFixed(2);
        document.getElementById('checkoutTotal').textContent = '<?php echo DEFAULT_CURRENCY; ?>' + total.toFixed(2);
    } else {
        cartCount.classList.add('hidden');
        emptyCart.classList.remove('hidden');
        cartItemsList.classList.add('hidden');
        cartSummary.classList.add('hidden');
    }
}

function openCart() {
    document.getElementById('cartModal').classList.remove('hidden');
}

function closeCart() {
    document.getElementById('cartModal').classList.add('hidden');
}

function openCheckout() {
    const isLoggedIn = <?php echo isLoggedIn() && hasRole('customer') ? 'true' : 'false'; ?>;

    if (!isLoggedIn) {
        alert('Please login to place an order');
        window.location.href = '<?php echo SITE_URL; ?>/auth.php';
        return;
    }

    closeCart();
    document.getElementById('checkoutModal').classList.remove('hidden');
}

function closeCheckout() {
    document.getElementById('checkoutModal').classList.add('hidden');
}

function closeSuccess() {
    document.getElementById('successModal').classList.add('hidden');
    cart = [];
    updateCartUI();
}

function togglePaymentDetails() {
    const selectedMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
    const bankDetails = document.getElementById('bankTransferDetails');
    const posDetails = document.getElementById('posDetails');

    // Hide all details first
    bankDetails.classList.add('hidden');
    posDetails.classList.add('hidden');

    // Show selected payment details
    if (selectedMethod === 'bank_transfer') {
        bankDetails.classList.remove('hidden');
    } else if (selectedMethod === 'pos') {
        posDetails.classList.remove('hidden');
    }
}

function previewScreenshot(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.querySelector('img').src = e.target.result;
            preview.classList.remove('hidden');
        }
        reader.readAsDataURL(file);
    } else {
        preview.classList.add('hidden');
    }
}

function clearScreenshot(inputId, previewId, textId) {
    document.getElementById(inputId).value = '';
    document.getElementById(previewId).classList.add('hidden');
    document.getElementById(textId).textContent = inputId.includes('bank') ? 'Upload payment screenshot' : 'Upload receipt screenshot';
}

function filterCategory(category) {
    document.querySelectorAll('.category-tab').forEach(tab => {
        tab.classList.remove('bg-gradient-to-r', 'from-amber-500', 'to-orange-600', 'text-white', 'shadow-lg');
        tab.classList.add('bg-white', 'text-gray-600', 'border', 'border-gray-200');
    });
    document.querySelector(`.category-tab[data-category="${category}"]`).classList.remove('bg-white', 'text-gray-600', 'border', 'border-gray-200');
    document.querySelector(`.category-tab[data-category="${category}"]`).classList.add('bg-gradient-to-r', 'from-amber-500', 'to-orange-600', 'text-white', 'shadow-lg');

    document.querySelectorAll('.menu-item').forEach(item => {
        if (category === 'all' || item.dataset.category === category) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function showToast(message) {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    toastMessage.textContent = message;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3000);
}

// Checkout form submission
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    e.preventDefault();

    if (cart.length === 0) {
        alert('Your cart is empty');
        return;
    }

    const formData = new FormData(this);
    const items = cart.map(item => ({
        name: item.name,
        quantity: item.quantity,
        price: item.price
    }));

    formData.append('action', 'create');
    formData.append('delivery_address', formData.get('address'));
    formData.append('special_instructions', formData.get('instructions'));
    formData.append('payment_method', document.querySelector('input[name="payment_method"]:checked').value);
    formData.append('items_json', JSON.stringify(items));

    fetch('<?php echo SITE_URL; ?>/api/place-order.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            closeCheckout();
            document.getElementById('orderNumber').textContent = data.order_number;
            document.getElementById('successModal').classList.remove('hidden');
            this.reset();
        } else {
            alert(data.message || 'Failed to place order');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Failed to place order. Please try again.');
    });
});
</script>

</body>
</html>
