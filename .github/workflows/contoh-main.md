name: Deploy Laravel Admin to Hpanel

on:
  push:
    branches: ["main-#1"] # Menggunakan branch baru sesuai permintaanmu

jobs:
  deploy:
    runs-on: ubuntu-latest
    name: Deploy Admin Panel to Hpanel

    steps:
      - name: Checkout code
        uses: actions/checkout@v3 # Upgrade ke v3 untuk stabilitas lebih baik

      - name: Setup SSH and Deploy
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USERNAME }}
          key: ${{ secrets.SERVER_KEY }}
          port: ${{ secrets.SERVER_PORT }}
          script: |
            # Path ke direktori admin di server
            ADMIN_DIR=/home/u982131153/domains/artiknesia.com/admin_ARTIKNESIA

            # 1. Cek repositori & update URL jika perlu
            if [ ! -d "$ADMIN_DIR" ]; then
              # Menggunakan URL repositori yang baru
              git clone https://github.com/devartiknesia/admin_ARTIKNESIA.git $ADMIN_DIR
            fi

            cd $ADMIN_DIR

            # 2. Update origin URL (untuk memastikan remote mengarah ke akun baru)
            git remote set-url origin https://github.com/devartiknesia/admin_ARTIKNESIA.git

            # 3. Setup environment path
            chmod +x ~/bin/composer
            export PATH=$HOME/bin:$PATH
            export PATH=$HOME/bin/node/bin:$PATH

            # 4. Pull kode terbaru dari branch main-#1
            git reset --hard
            git stash
            git fetch origin
            git checkout "main-#1"
            git pull origin "main-#1"

            # 5. Node.js setup & Build
            npm install --legacy-peer-deps
            npm run prod

            # 6. Composer setup
            $HOME/bin/composer install --no-dev --optimize-autoloader

            # 7. Laravel setup (Optimasi)
            php artisan migrate:sync --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan optimize

            # 8. Setup symlink ke public_html (Check existence first)
            if [ ! -L "/home/u982131153/domains/artiknesia.com/public_html/admin" ]; then
              ln -s $ADMIN_DIR/public /home/u982131153/domains/artiknesia.com/public_html/admin
            fi