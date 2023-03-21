# Release steps

## 1. Bump plugin version in `trunk`

1. Update plugin version in `vip-block-data-api.php`. Change plugin header and `WPCOMVIP__BLOCK_DATA_API__PLUGIN_VERSION` to match new version.
2. PR and merge to `trunk`.

## 2. Tag branch for release

1. Add a tag for the release:

    ```bash
    git tag -a <version> -m "Release <version>"

    # e.g. git tag -a v0.1.0-alpha -m "Release v0.1.0-alpha"
    ```

2. Run `git push --tags`.

## 3. Create a release

1. In the `vip-block-data-api` folder, run this command to create a plugin ZIP:

    ```bash
    zip -r - ./ -x "./.*" "./.*/*" "*.zip" > vip-block-data-api-<version>.zip

    # -r: Recursively
    # - : Output to STDOUT
    # ./: Use files in the current directory
    # -x: Exclude files
    ```

2. Visit the [vip-block-data-api create release page](https://github.com/Automattic/vip-block-data-api/releases/new).
3. Select the newly created version tag in the dropdown.
4. For the title, enter the release version name (e.g. `v0.1.0-alpha`)
5. Add a description of release changes.
6. Attach the plugin ZIP.
7. Click "Publish release."
