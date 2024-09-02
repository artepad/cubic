<!-- ============================================================== -->
                <!-- End Content -->
                <!-- ============================================================== -->
                </div>
            <!-- /.container-fluid -->
            <footer class="footer text-center"> 2024 &copy; Schaaf Producciones </footer>
        </div>
        <!-- ============================================================== -->
        <!-- End Page Content -->
        <!-- ============================================================== -->
    </div>
    <!-- ============================================================== -->
    <!-- End Wrapper -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- All Jquery -->
    <!-- ============================================================== -->
    <script src="<?php echo APP_URL; ?>/plugins/bower_components/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap Core JavaScript -->
    <script src="<?php echo APP_URL; ?>/bootstrap/dist/js/bootstrap.min.js"></script>
    <!-- Menu Plugin JavaScript -->
    <script src="<?php echo APP_URL; ?>/plugins/bower_components/sidebar-nav/dist/sidebar-nav.min.js"></script>
    <!--slimscroll JavaScript -->
    <script src="<?php echo APP_URL; ?>/js/jquery.slimscroll.js"></script>
    <!--Wave Effects -->
    <script src="<?php echo APP_URL; ?>/js/waves.js"></script>
    <!--Counter js -->
    <script src="<?php echo APP_URL; ?>/plugins/bower_components/waypoints/lib/jquery.waypoints.js"></script>
    <script src="<?php echo APP_URL; ?>/plugins/bower_components/counterup/jquery.counterup.min.js"></script>
    <!-- chartist chart -->
    <script src="<?php echo APP_URL; ?>/plugins/bower_components/chartist-js/dist/chartist.min.js"></script>
    <script src="<?php echo APP_URL; ?>/plugins/bower_components/chartist-plugin-tooltip-master/dist/chartist-plugin-tooltip.min.js"></script>
    <!-- Sparkline chart JavaScript -->
    <script src="<?php echo APP_URL; ?>/plugins/bower_components/jquery-sparkline/jquery.sparkline.min.js"></script>
    <!-- Custom Theme JavaScript -->
    <script src="<?php echo APP_URL; ?>/js/custom.min.js"></script>
    <script src="<?php echo APP_URL; ?>/js/dashboard1.js"></script>
    <script src="<?php echo APP_URL; ?>/plugins/bower_components/toast-master/js/jquery.toast.js"></script>
    <?php if (isset($pageSpecificScripts)): ?>
        <?php foreach ($pageSpecificScripts as $script): ?>
            <script src="<?php echo APP_URL . $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    <script>
        $(document).ready(function() {
            // Aquí puedes agregar cualquier JavaScript que quieras que se ejecute en todas las páginas
        });
    </script>
</body>
</html>