<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center - BING</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/help.css">
</head>
<body>
    <?php include('navbar.php'); ?>
    
    <div class="help-container">
        <div class="help-header">
            <h1 class="help-title">Help Center</h1>
            <p class="help-subtitle">
                Find answers to common questions, troubleshoot issues, or contact our support team. 
                We're here to help you get the most out of your BING experience.
            </p>
        </div>

        <div class="faq-section" id="account">
            <h2 class="section-title">
                <i class="fas fa-question-circle"></i> Frequently Asked Questions
            </h2>
            
            <div class="faq-item">
                <div class="faq-question">
                    How do I change my subscription plan?
                </div>
                <div class="faq-answer">
                    You can change your subscription plan at any time from your Account Settings page. 
                    Navigate to "Membership & Billing" and click "Change Plan" to view available options. 
                    Upgrades take effect immediately, while downgrades apply at your next billing cycle.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    Why was I charged when I canceled my subscription?
                </div>
                <div class="faq-answer">
                    When you cancel, you can continue watching until the end of your current billing period. 
                    You won't be charged again unless you restart your subscription. The charge you see is 
                    for the period before you canceled.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    How do I update my payment method?
                </div>
                <div class="faq-answer">
                    To update your payment information, go to Account Settings > Membership & Billing. 
                    Click "Update Payment Method" and enter your new card details. Your changes will 
                    be saved for future payments.
                </div>
            </div>
            
            <div class="faq-item" id="streaming">
                <div class="faq-question">
                    Why does my video keep buffering?
                </div>
                <div class="faq-answer">
                    Buffering issues are usually related to your internet connection. Try these steps:
                    <ul style="margin-top: 10px; padding-left: 20px;">
                        <li>Check your internet speed (we recommend at least 5 Mbps for HD)</li>
                        <li>Restart your router and device</li>
                        <li>Reduce the number of devices using your network</li>
                        <li>Lower the video quality in playback settings</li>
                    </ul>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    Why can't I watch in 4K?
                </div>
                <div class="faq-answer">
                    4K streaming requires:
                    <ul style="margin-top: 10px; padding-left: 20px;">
                        <li>A 4K-capable device and display</li>
                        <li>Internet speed of at least 25 Mbps</li>
                        <li>A Premium or Ultra HD subscription plan</li>
                        <li>Content that's available in 4K (look for the Ultra HD badge)</li>
                    </ul>
                </div>
            </div>
            
            <div class="faq-item" id="devices">
                <div class="faq-question">
                    How do I download movies to watch offline?
                </div>
                <div class="faq-answer">
                    To download content:
                    <ol style="margin-top: 10px; padding-left: 20px;">
                        <li>Open the BING app on your mobile device or tablet</li>
                        <li>Find the movie or show you want to download</li>
                        <li>Look for the download icon (downward arrow)</li>
                        <li>Select download quality if prompted</li>
                        <li>Wait for the download to complete</li>
                    </ol>
                    Note: Not all content is available for download due to licensing restrictions.
                </div>
            </div>
            
            <div class="faq-item" id="profiles">
                <div class="faq-question">
                    How many profiles can I create?
                </div>
                <div class="faq-answer">
                    The number of profiles depends on your subscription plan:
                    <ul style="margin-top: 10px; padding-left: 20px;">
                        <li>Basic: 2 profiles</li>
                        <li>Standard: 4 profiles</li>
                        <li>Premium: 6 profiles</li>
                    </ul>
                    Each profile has its own watch history, recommendations, and settings.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    How do I set up parental controls?
                </div>
                <div class="faq-answer">
                    To set up parental controls:
                    <ol style="margin-top: 10px; padding-left: 20px;">
                        <li>Go to Account Settings > Profiles</li>
                        <li>Select the profile you want to restrict</li>
                        <li>Click "Parental Controls"</li>
                        <li>Set a PIN and choose the maturity level</li>
                        <li>Save your changes</li>
                    </ol>
                    Kids profiles automatically filter content to be age-appropriate.
                </div>
            </div>
        </div>
        
        <div class="contact-section">
            <h2 class="section-title">
                <i class="fas fa-headset"></i> Contact Support
            </h2>
            <p style="color: var(--gray); margin-bottom: 20px; line-height: 1.6;">
                Can't find what you're looking for? Our support team is available 24/7 to help with any questions or issues.
            </p>
            
            <div class="contact-methods">
                <div class="contact-method">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3 class="contact-title">Email Us</h3>
                    <p class="contact-info">
                        We'll respond within 24 hours.
                    </p>
                    <a href="mailto:support@bingstream.com" class="contact-link">support@bingstream.com</a>
                </div>
                
                <div class="contact-method">
                    <div class="contact-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <h3 class="contact-title">Call Us</h3>
                    <p class="contact-info">
                        Available 24/7 for urgent issues.
                    </p>
                    <a href="tel:+18005551234" class="contact-link">1-800-555-1234</a>
                </div>
                
                <div class="contact-method">
                    <div class="contact-icon">
                        <i class="fas fa-twitter"></i>
                    </div>
                    <h3 class="contact-title">Twitter</h3>
                    <p class="contact-info">
                        Tweet us @BINGSupport for quick help.
                    </p>
                    <a href="https://twitter.com/BINGSupport" target="_blank" class="contact-link">@BINGSupport</a>
                </div>
            </div>
        </div>
    </div>
    <script src="/js/help.js"></script>
</body>
</html>