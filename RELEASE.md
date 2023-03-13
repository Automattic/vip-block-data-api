# Release steps

## 1. Bump plugin version in `trunk`

1. Update plugin version in `vip-content-api.php`. Change plugin header and `WPCOMVIP__CONTENT_API__PLUGIN_VERSION` to match new version.
2. PR and merge to `trunk`.

## 2. Merge to `release` branch and tag

1. Merge `trunk` changes into the `release` branch:

    ```bash
    git checkout release
    git merge trunk
    ```

2. If `composer` dependencies have changed, run `composer install --no-dev` and commit changes.
3. Add a tag for the release:

    ```bash
    git tag -a <version> -m "Release <version>"

    # e.g. git tag -a v0.1.0-alpha -m "Release v0.1.0-alpha"
    ```

5. Run `git push --tags`.

## 3. Create a release

1. In the `vip-content-api` folder, run this command to create a plugin ZIP:

    ```bash
    zip -r - ./ -x "./.git/*" "./.github/*" "*.zip" > vip-content-api.zip

    # -r: Recursively
    # - : Output to STDOUT
    # ./: Use files in the current directory
    # -x: Exclude files
    ```

2. Visit the [vip-content-api create release page](https://github.com/Automattic/vip-content-api/releases/new).
3. Select the newly created version tag in the dropdown.
4. For the title, enter the release version name (e.g. `v0.1.0-alpha`)
5. Add a description of release changes.
6. Attach the plugin ZIP.
7. Click "Publish release."
