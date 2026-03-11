public function up()
{
    Schema::table('events', function (Blueprint $table) {
        $table->string('calendar_type')->default('private');
    });
}

public function down()
{
    Schema::table('events', function (Blueprint $table) {
        $table->dropColumn('calendar_type');
    });
}