<?php
require_once __DIR__ . '/site_config.php';

$footer = SiteConfig::footerForDisplay();
$footerIcpText = $footer['icp_text'];
$footerIcpUrl  = $footer['icp_url'];
$footerGaText  = $footer['ga_text'];
$footerGaUrl   = $footer['ga_url'];
?>
<footer class="site-footer">
    <div class="footer-inner">
        <p class="copyright">&copy; 2026 瓷都名汇. All rights reserved.</p>
        <div class="footer-beian">
<?php if ($footerIcpText !== ''): ?>
            <a href="<?php echo htmlspecialchars($footerIcpUrl); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($footerIcpText); ?></a>
<?php endif; ?>
<?php if ($footerGaText !== ''): ?>
            <a href="<?php echo htmlspecialchars($footerGaUrl); ?>" target="_blank" rel="noopener" class="beian-ga">
                <img src="assets/img/IMG_GA.png" alt="公安备案">
                <?php echo htmlspecialchars($footerGaText); ?>
            </a>
<?php endif; ?>
        </div>
    </div>
</footer>
