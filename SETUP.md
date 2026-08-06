# DragonFable Private Server - Setup

The updated guide is available at [here](https://github.com/hiperesp/DragonFable-Private-Server/blob/main/SETUP.md)

## Fastest way to setup the server (windows only):

<a href="https://www.mediafire.com/file/kvjx7h0doe7cbp4/Tutorial_install.mp4/file"> Video Tutorial Install Part 1

<a href="https://www.mediafire.com/file/432cl9f4hctvrtj/Part_2_The_result.mp4/file"> Video Tutorial Install Part 2

## Using Docker:

### Prerequisites:

- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/install/)
- [Git](https://git-scm.com/downloads) (Optional)

**Note**: If you don't have Git installed, you can download the repository as a ZIP file by clicking the green "Code" button at the top of the repository page.

### Steps:

1. Clone this repository(Copy paste in cmd/powershell):
    ```sh
    git clone https://github.com/shandymodders/DragonFable-Private-Server
    ```
2. copy paste this also to cmd
   ```sh
    cd DragonFable-Private-Server
    ```
3. last Copy and paste to cmd:
    ```sh
    docker-compose up
    ```

3. Configure the server:
    - Using environment variables:
        Read the `/src/server-emulator/.config.default.php` file and set the environment variables accordingly.
    - Using a .config.php file:
        Copy the `/src/server-emulator/.config.default.php` to `/src/server-emulator/.config.php` and edit the values as needed.

    You can use MySQL or SQLite. See `/src/server-emulator/.config.default.php` for more details.

    **Note**: If you don't create the `.config.php` file, the server will use the default system environment variables.

4. (Optional) Download the offline gamefiles from [mega, click here](https://mega.nz/file/vQ9CDQKK#xa3g9WW8sAGukSoDhtBThgpwA2KSfHke2RalfC4ZO7k) (updated at 2025-08-27) and extract it to `/src/cdn/gamefiles/`\
    **Note**: If you don't download the gamefiles, the server will progressively download them as each game file is requested **only if you use the `cache` mode at `gamefilesPath` settings**. This means that as you play, the server will fetch the necessary files in real-time, ensuring you can continue playing without interruption.

### Usage:

1. Start the server using Docker Compose:
    ```sh
    docker-compose up
    ```

2. Access the game at `http://localhost:40000` in your browser.

3. Setup the database using [upgrade tool](UPGRADE.md).

4. Now you can play the game!

## Without Docker (using shared hosting also):

### Prerequisites:

- PHP 8.4 + Apache2 in server
- MySQL or SQLite support in php
- Git (Optional)

**Note**: If you don't have Git installed, you can download the repository as a ZIP file by clicking the green "Code" button at the top of the repository page.

### Steps:

1. Clone this repository to your local machine:
    ```sh
    git clone https://github.com/hiperesp/DragonFable-Private-Server/
    ```

2. Upload the following dirs to your hosting provider:
    - `/src/cdn/` to `public_html/cdn/`
    - `/src/server-emulator/` to `public_html/server-emulator/`
    - `/src/web/` to `public_html/` (move the files to the root dir)

3. Configure the server:
    Copy the `/src/server-emulator/.config.default.php` to `/src/server-emulator/.config.php` and edit the values as needed.

    You can use MySQL or SQLite. See `/src/server-emulator/.config.default.php` for more details. By default, the server will use SQLite.

4. (Optional) Download the offline gamefiles from [mega, click here](https://mega.nz/file/vQ9CDQKK#xa3g9WW8sAGukSoDhtBThgpwA2KSfHke2RalfC4ZO7k) (updated at 2025-08-27) and extract it to `public_html/cdn/gamefiles/`\
    **Note**: If you don't download the gamefiles, the server will progressively download them as each game file is requested **only if you use the `cache` mode at `gamefilesPath` settings**. This means that as you play, the server will fetch the necessary files in real-time, ensuring you can continue playing without interruption.

### Usage:

1. The server should be running.

2. Access the game using your domain in your browser.

3. Setup the database using [upgrade tool](UPGRADE.md).

4. Now you can play the game!

### 4. Upgrading the server:

If you want to upgrade the server to a newer version, you can follow the steps below:

1. If you are using SQLite, backup the `db.sqlite3` file. If you used the default path, this file is located in `server-emulator/data/db.sqlite3`.

2. Delete all the files in the `htdocs` folder.

3. Do the same steps 4 to 8 in the `Prepare files` section.

4. If you are using SQLite, move the `db.sqlite3` file you backed up to the old path.

5. Access the setup page at `http://localhost/setup.html` in your browser.

6. Select the database type you are using, fill the fields with the same values you used before.

7. Click the `Setup` button and wait some minutes. The server will create the necessary tables and will add all the game data.

8. After the setup is complete, you can play the game at `http://localhost/`.
