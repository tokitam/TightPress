<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TightPress</title>
<link rel="stylesheet" href="<?php echo esc_url(get_stylesheet_uri()); ?>">
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
      <h2><a href="<?php echo get_permalink(); ?>"><?php the_title(); ?></a></h2>
      <p><?php the_excerpt(); ?></p>
    </article>
  <?php endwhile; ?>
<?php else: ?>
  <p>記事がありません。</p>
<?php endif; ?>
</main>
</body>
</html>
