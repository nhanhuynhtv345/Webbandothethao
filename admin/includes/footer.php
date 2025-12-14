            </main>
            
            <!-- Footer -->
            <footer class="bg-white border-t px-6 py-4">
                <div class="flex items-center justify-between text-sm text-slate-500">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                    <p>Made with <i class="fas fa-heart text-red-500"></i> by NTH Team</p>
                </div>
            </footer>
        </div>
    </div>
    
    <script>
    function confirmDelete(message = 'Bạn có chắc chắn muốn xóa?') {
        return confirm(message);
    }
    
    // Add smooth animations
    document.addEventListener('DOMContentLoaded', function() {
        // Animate cards on load
        const cards = document.querySelectorAll('.card-hover');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
    </script>
</body>
</html>
