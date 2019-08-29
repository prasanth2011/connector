Flysystem Magiq
=================

For setup instructions see the Flysystem README.txt.

## CONFIGURATION ##

Example configuration:

$schemes = [
  'magiq' => [
    'driver' => 'magiq',
    'config' => [
      'username' => 'User Name',
      'password' => 'Password',

      // Optional.
      'prefix' => 'a/sub/directory',
      'token' => 'a-long-token-string',
      'public' => TRUE, // Serve files directly via Dropbox.
    ],
  ],
];

$settings['flysystem'] = $schemes;
