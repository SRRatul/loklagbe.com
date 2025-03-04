document.addEventListener("DOMContentLoaded", function () {
    // Fetch and update banner image (mock function)
    function updateBanner() {
        const bannerImg = document.getElementById("adminBanner");
        const uploadedBanner = localStorage.getItem("adminBanner"); // Simulating admin upload
        if (uploadedBanner) {
            bannerImg.src = uploadedBanner;
        }
    }

    updateBanner();
});
