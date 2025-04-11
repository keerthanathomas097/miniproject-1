from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time

class TestAdminNavigation:
    def setup_method(self, method):
        self.driver = webdriver.Chrome()
        self.driver.implicitly_wait(10)
        self.base_url = "http://localhost/MiniOrg/"
    
    def teardown_method(self, method):
        self.driver.quit()
    
    def test_navigate_admin_pages(self):
        try:
            # Navigate to admin dashboard
            print("\n🌐 Navigating to Admin Dashboard...")
            self.driver.get(self.base_url + "admin_dashboard.php")
            print("✅ Successfully loaded admin dashboard page")
            
            # Wait a moment to see the page
            time.sleep(2)
            
            # Navigate to orders admin page
            print("\n🌐 Navigating to Orders Admin Page...")
            self.driver.get(self.base_url + "orders_admin.php")
            print("✅ Successfully loaded orders admin page")
            
            # Print final success message
            print("\n✨ Navigation Test Completed Successfully!")
            print("   - Visited admin_dashboard.php")
            print("   - Visited orders_admin.php")
            
        except Exception as e:
            print(f"\n❌ Error during navigation: {str(e)}")
            raise

if __name__ == "__main__":
    test = TestAdminNavigation()
    test.setup_method(None)
    test.test_navigate_admin_pages()
    test.teardown_method(None) 