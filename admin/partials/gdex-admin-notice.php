<div class="notice notice-<?= $notice['type'] ?> gdex-notice">
	<?php if ( $notice['title'] ): ?>
        <h4><?= $notice['title'] ?></h4>
	<?php endif; ?>

    <p><?= $notice['message'] ?></p>

	<?php if ( $notice['list'] ): ?>
        <ul>
			<?php foreach ( $notice['list'] as $item ): ?>
                <li><?= $item ?></li>
			<?php endforeach; ?>
        </ul>
	<?php endif; ?>
</div>