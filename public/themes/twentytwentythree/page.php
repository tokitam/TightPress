<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc_html(get_the_title()); ?> - TightPress</title>
</head>
<body>
<header>
  <h1><a href="<?php echo home_url('/'); ?>">TightPress</a></h1>
</header>
<main>
<?php if ($tp_context->hasPosts()): ?>
  <?php while ($tp_context->hasPosts()): ?>
    <?php the_post(); ?>
    <article>
      <h2><?php the_title(); ?></h2>
      <div class="content"><?php the_content(); ?></div>
    </article>
  <?php endwhile; ?>
<?php else: ?>
  <p>ページが見つかりません。</p>
<?php endif; ?>
</main>
<p><a href="<?php echo home_url('/'); ?>">&laquo; トップへ戻る</a></p>
</body>
</html>
