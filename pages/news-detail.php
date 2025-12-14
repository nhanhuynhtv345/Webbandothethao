<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$slug = $_GET['slug'] ?? '';
$db = getDB();

if (empty($slug)) {
    redirect(SITE_URL . '/pages/news.php');
}

// Get news
$stmt = $db->prepare("SELECT * FROM tin_tuc WHERE slug = ? AND status = 'published'");
$stmt->execute([$slug]);
$news = $stmt->fetch();

if (!$news) {
    redirect(SITE_URL . '/pages/news.php');
}

// Update view count
$db->prepare("UPDATE tin_tuc SET luot_xem = luot_xem + 1 WHERE id = ?")->execute([$news['id']]);

// Get related news
$relatedStmt = $db->prepare("SELECT * FROM tin_tuc WHERE status = 'published' AND id != ? ORDER BY published_at DESC LIMIT 3");
$relatedStmt->execute([$news['id']]);
$relatedNews = $relatedStmt->fetchAll();

$pageTitle = $news['title'];
$metaDescription = $news['summary'] ?? '';

include __DIR__ . '/../components/header.php';
?>

<style>
.news-content { line-height: 1.8; font-size: 1.0625rem; }
.news-content h2 { font-size: 1.875rem; font-weight: 700; margin-top: 2rem; margin-bottom: 1rem; color: #1f2937; }
.news-content h3 { font-size: 1.5rem; font-weight: 600; margin-top: 1.5rem; margin-bottom: 0.75rem; color: #374151; }
.news-content p { margin-bottom: 1.25rem; color: #4b5563; }
.news-content ul, .news-content ol { margin-left: 1.5rem; margin-bottom: 1.25rem; }
.news-content li { margin-bottom: 0.5rem; }
.news-content strong { font-weight: 600; color: #1f2937; }
.news-content img { max-width: 100%; height: auto; border-radius: 0.5rem; margin: 1.5rem 0; }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>

<!-- Breadcrumb -->
<div class="bg-gray-50 py-4 border-b">
    <div class="container mx-auto px-4">
        <nav class="flex items-center gap-2 text-sm">
            <a href="<?php echo SITE_URL; ?>/index.php" class="text-gray-600 hover:text-primary-600">
                <i class="fas fa-home"></i> Trang chủ
            </a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <a href="<?php echo SITE_URL; ?>/pages/news.php" class="text-gray-600 hover:text-primary-600">Tin tức</a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-900 font-medium line-clamp-1"><?php echo htmlspecialchars($news['title']); ?></span>
        </nav>
    </div>
</div>

<div class="container mx-auto px-4 py-12">
    <div class="max-w-4xl mx-auto">
        <!-- Article -->
        <article class="bg-white rounded-lg shadow-lg overflow-hidden mb-8">
            <?php if ($news['cover_image']): ?>
            <div class="h-96 overflow-hidden">
                <img src="<?php echo UPLOAD_URL . '/' . htmlspecialchars($news['cover_image']); ?>" 
                     alt="<?php echo htmlspecialchars($news['title']); ?>"
                     class="w-full h-full object-cover">
            </div>
            <?php endif; ?>
            
            <div class="p-8 md:p-12">
                <h1 class="text-4xl md:text-5xl font-bold mb-6 text-gray-900">
                    <?php echo htmlspecialchars($news['title']); ?>
                </h1>
                
                <div class="flex flex-wrap items-center gap-6 text-gray-600 mb-8 pb-6 border-b">
                    <div class="flex items-center gap-2">
                        <i class="far fa-calendar text-primary-600"></i>
                        <span><?php echo date('d/m/Y H:i', strtotime($news['published_at'])); ?></span>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <i class="far fa-eye text-primary-600"></i>
                        <span><?php echo number_format($news['luot_xem']); ?> lượt xem</span>
                    </div>
                    
                    <?php if ($news['tags']): ?>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach (explode(',', $news['tags']) as $tag): ?>
                        <span class="text-xs bg-primary-100 text-primary-700 px-2 py-1 rounded"><?php echo trim($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($news['summary']): ?>
                <div class="bg-primary-50 border-l-4 border-primary-600 p-6 mb-8 rounded-r">
                    <p class="text-lg text-gray-700 font-medium italic leading-relaxed">
                        <?php echo htmlspecialchars($news['summary']); ?>
                    </p>
                </div>
                <?php endif; ?>
                
                <div class="news-content">
                    <?php echo $news['content']; ?>
                </div>
                
                <!-- Share -->
                <div class="mt-12 pt-8 border-t">
                    <div class="flex items-center gap-4">
                        <span class="text-gray-700 font-semibold text-lg">Chia sẻ:</span>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(SITE_URL . $_SERVER['REQUEST_URI']); ?>" 
                           target="_blank"
                           class="w-12 h-12 flex items-center justify-center rounded-full bg-blue-600 text-white hover:bg-blue-700 transition text-xl">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(SITE_URL . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($news['title']); ?>" 
                           target="_blank"
                           class="w-12 h-12 flex items-center justify-center rounded-full bg-sky-500 text-white hover:bg-sky-600 transition text-xl">
                            <i class="fab fa-twitter"></i>
                        </a>
                    </div>
                </div>
            </div>
        </article>
        
        <!-- Related News -->
        <?php if (!empty($relatedNews)): ?>
        <div class="bg-white rounded-lg shadow-lg p-8 md:p-12">
            <h2 class="text-3xl font-bold mb-8 flex items-center gap-3">
                <i class="fas fa-newspaper text-primary-600"></i>
                Tin tức liên quan
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($relatedNews as $related): ?>
                <article class="group">
                    <a href="<?php echo SITE_URL; ?>/pages/news-detail.php?slug=<?php echo $related['slug']; ?>" class="block">
                        <?php if ($related['cover_image']): ?>
                        <div class="overflow-hidden rounded-lg mb-4 h-40">
                            <img src="<?php echo UPLOAD_URL . '/' . htmlspecialchars($related['cover_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($related['title']); ?>"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                        </div>
                        <?php else: ?>
                        <div class="h-40 bg-gradient-to-br from-primary-400 to-primary-600 rounded-lg mb-4 flex items-center justify-center">
                            <i class="fas fa-newspaper text-white text-4xl"></i>
                        </div>
                        <?php endif; ?>
                        
                        <h3 class="font-bold text-lg mb-2 line-clamp-2 group-hover:text-primary-600 transition-colors">
                            <?php echo htmlspecialchars($related['title']); ?>
                        </h3>
                        
                        <div class="flex items-center gap-4 text-sm text-gray-600">
                            <span><i class="far fa-calendar mr-1"></i><?php echo date('d/m/Y', strtotime($related['published_at'])); ?></span>
                            <span><i class="far fa-eye mr-1"></i><?php echo number_format($related['luot_xem']); ?></span>
                        </div>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-8">
                <a href="<?php echo SITE_URL; ?>/pages/news.php" 
                   class="inline-flex items-center justify-center px-8 py-3 bg-primary-600 text-white rounded-lg font-semibold hover:bg-primary-700 transition">
                    Xem tất cả tin tức <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
