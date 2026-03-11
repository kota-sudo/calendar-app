public function up()
{
    Schema::table('events', function (Blueprint $table) {
        $table->integer('notification_minutes')->nullable();
    });
}

public function down()
{
    Schema::table('events', function (Blueprint $table) {
        $table->dropColumn('notification_minutes');
    });
}