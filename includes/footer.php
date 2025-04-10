</main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-12">
        <div class="max-w-7xl mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- About Section -->
                <div class="col-span-1 md:col-span-2">
                    <h3 class="text-xl font-bold mb-4">AO Courses</h3>
                    <p class="text-gray-300 mb-4">
                        Empowering Angolan students with quality online education. 
                        Learn at your own pace with our comprehensive course library.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-300 hover:text-white">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="/about.php" class="text-gray-300 hover:text-white">About Us</a>
                        </li>
                        <li>
                            <a href="/courses.php" class="text-gray-300 hover:text-white">Courses</a>
                        </li>
                        <li>
                            <a href="/contact.php" class="text-gray-300 hover:text-white">Contact</a>
                        </li>
                        <li>
                            <a href="/faq.php" class="text-gray-300 hover:text-white">FAQ</a>
                        </li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contact Us</h3>
                    <ul class="space-y-2">
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-map-marker-alt w-5"></i>
                            <span>Luanda, Angola</span>
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-phone w-5"></i>
                            <span>+244 123 456 789</span>
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-envelope w-5"></i>
                            <span>info@aocourses.com</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Copyright -->
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-300">
                <p>&copy; <?php echo date('Y'); ?> AO Courses. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Mobile Menu JavaScript -->
    <script>
        // Mobile menu toggle
        const mobileMenuButton = document.querySelector('.mobile-menu-button');
        const mobileMenu = document.querySelector('.mobile-menu');

        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!mobileMenu.contains(e.target) && !mobileMenuButton.contains(e.target)) {
                mobileMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
